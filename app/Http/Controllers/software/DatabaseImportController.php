<?php

namespace App\Http\Controllers\software;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class DatabaseImportController extends Controller
{
    public function __construct()
    {
        // Public/guest access
        $this->middleware('guest');
    }

    public function verifyPassword(Request $request)
    {
        $password = $request->input('password');
        if ($password === 'Ocean@15892') {
            session(['db_import_unlocked' => true]);
            return response()->json(['success' => true]);
        }
        return response()->json(['success' => false, 'message' => 'Incorrect password! Please try again.']);
    }

    public function showImportForm()
    {
        $unlocked = session('db_import_unlocked', false);
        return view('software.auth.db-import', compact('unlocked'));
    }

    public function downloadBackup()
    {
        if (!session('db_import_unlocked', false)) {
            return redirect()->route('software.db-import')->with('error', 'Password verification required.');
        }

        try {
            set_time_limit(300);
            ini_set('memory_limit', '512M');

            $dbName = DB::getDatabaseName();
            $tables = DB::select('SHOW TABLES');
            $sqlDump = "-- Database Backup for {$dbName}\n";
            $sqlDump .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
            $sqlDump .= "-- ------------------------------------------------------\n\n";
            $sqlDump .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

            foreach ($tables as $table) {
                $tableArray = (array) $table;
                $tableName = reset($tableArray);

                $sqlDump .= "-- ------------------------------------------------------\n";
                $sqlDump .= "-- Table structure for table `{$tableName}`\n";
                $sqlDump .= "-- ------------------------------------------------------\n";
                $sqlDump .= "DROP TABLE IF EXISTS `{$tableName}`;\n";

                $createTable = DB::select("SHOW CREATE TABLE `{$tableName}`");
                $createTableArray = (array) $createTable[0];
                $sqlDump .= $createTableArray['Create Table'] . ";\n\n";

                $sqlDump .= "-- Dump data for table `{$tableName}`\n";
                $rows = DB::table($tableName)->get();

                if ($rows->count() > 0) {
                    $sqlDump .= "INSERT INTO `{$tableName}` VALUES \n";
                    $insertRows = [];
                    foreach ($rows as $row) {
                        $values = [];
                        foreach ((array) $row as $column => $value) {
                            if (is_null($value)) {
                                $values[] = 'NULL';
                            } else {
                                $values[] = "'" . addslashes($value) . "'";
                            }
                        }
                        $insertRows[] = "(" . implode(', ', $values) . ")";
                    }
                    $sqlDump .= implode(",\n", $insertRows) . ";\n\n";
                }
            }

            $sqlDump .= "SET FOREIGN_KEY_CHECKS=1;\n";

            $filename = 'backup_' . $dbName . '_' . date('Y_m_d_H_i_s') . '.sql';

            return response($sqlDump)
                ->header('Content-Type', 'application/sql')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');

        } catch (\Exception $e) {
            return back()->with('error', 'Backup failed: ' . $e->getMessage());
        }
    }

    public function parseSqlStatements($sqlContent)
    {
        // Remove SQL comments (both multi-line /* ... */ and single-line -- or #)
        $sqlContent = preg_replace('!/\*.*?\*/!s', '', $sqlContent);

        $lines = explode("\n", $sqlContent);
        $statements = [];
        $currentStatement = '';

        foreach ($lines as $line) {
            $trimmedLine = trim($line);

            // Skip empty lines, single-line comments
            if ($trimmedLine === '' || str_starts_with($trimmedLine, '--') || str_starts_with($trimmedLine, '#')) {
                continue;
            }

            // Skip DELIMITER keyword commonly found in sql dumps
            if (stripos($trimmedLine, 'DELIMITER') === 0) {
                continue;
            }

            $currentStatement .= $line . "\n";

            // If the statement ends with a semicolon, save it
            if (str_ends_with(rtrim($trimmedLine, ';'), ';') || str_ends_with($trimmedLine, ';')) {
                $statements[] = rtrim(trim($currentStatement), ';');
                $currentStatement = '';
            }
        }

        // Add any remaining statement
        if (trim($currentStatement) !== '') {
            $statements[] = rtrim(trim($currentStatement), ';');
        }

        // Clean up statements
        return array_values(array_filter(array_map('trim', $statements)));
    }

    public function uploadSql(Request $request)
    {
        if (!session('db_import_unlocked', false)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized. Password verification required.'], 403);
        }
        $request->validate([
            'sql_file' => 'required|file',
        ]);

        $file = $request->file('sql_file');

        // Ensure it has .sql extension
        if ($file->getClientOriginalExtension() !== 'sql') {
            return response()->json([
                'success' => false,
                'message' => 'Please upload a valid .sql file.',
            ], 422);
        }

        $sqlContent = file_get_contents($file->getRealPath());
        $statements = $this->parseSqlStatements($sqlContent);

        if (empty($statements)) {
            return response()->json([
                'success' => false,
                'message' => 'The uploaded SQL file does not contain any valid SQL statements.',
            ], 422);
        }

        $importId = (string) Str::uuid();
        $directory = storage_path('app/db_imports');

        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        File::put($directory . '/' . $importId . '.json', json_encode($statements));

        return response()->json([
            'success' => true,
            'import_id' => $importId,
            'total_statements' => count($statements),
            'message' => 'SQL file uploaded and parsed successfully!',
        ]);
    }

    public function executeChunk(Request $request)
    {
        if (!session('db_import_unlocked', false)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized. Password verification required.'], 403);
        }
        $request->validate([
            'import_id' => 'required|string',
            'offset' => 'required|integer|min:0',
            'chunk_size' => 'sometimes|integer|min:1',
        ]);

        $importId = $request->input('import_id');
        $offset = (int) $request->input('offset');
        $chunkSize = (int) $request->input('chunk_size', 20); // Default to 20 statements as requested

        $filePath = storage_path('app/db_imports/' . $importId . '.json');

        if (!File::exists($filePath)) {
            return response()->json([
                'success' => false,
                'message' => 'Import session expired or invalid. Please upload the file again.',
            ], 404);
        }

        $statements = json_decode(File::get($filePath), true);
        $total = count($statements);

        if ($offset >= $total) {
            // Completed! Clean up the file.
            File::delete($filePath);
            return response()->json([
                'success' => true,
                'completed' => true,
                'message' => 'All database statements imported successfully!',
            ]);
        }

        $chunk = array_slice($statements, $offset, $chunkSize);

        // Execute queries in this chunk
        // Layer 1 Protection: Disable Foreign Key Checks and temporarily clear strict sql_mode
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            DB::statement("SET SESSION sql_mode = ''");
        } catch (\Exception $e) {
            // Ignore if setting session mode fails on some DB configurations
        }

        foreach ($chunk as $index => $query) {
            if (empty(trim($query))) {
                continue;
            }
            try {
                DB::unprepared($query);
            } catch (\Exception $e) {
                // Layer 2 Protection: Try intelligent auto-solving of the query
                $solveResult = $this->autoSolveQuery($query, $e->getMessage());
                if ($solveResult['success']) {
                    // Update stored statement in JSON file so retry records are clean
                    $statements[$offset + $index] = $solveResult['solved_query'];
                    File::put($filePath, json_encode($statements));
                    continue; // Repaired & executed successfully, move forward!
                }

                DB::statement('SET FOREIGN_KEY_CHECKS=1');

                // Return failed query and its absolute index
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage(),
                    'query' => $query,
                    'failed_index' => $offset + $index,
                ]);
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        return response()->json([
            'success' => true,
            'completed' => false,
            'executed' => count($chunk),
            'next_offset' => $offset + count($chunk),
        ]);
    }

    public function executeSingle(Request $request)
    {
        if (!session('db_import_unlocked', false)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized. Password verification required.'], 403);
        }
        $request->validate([
            'import_id' => 'required|string',
            'index' => 'required|integer|min:0',
            'query' => 'required|string',
        ]);

        $importId = $request->input('import_id');
        $index = (int) $request->input('index');
        $query = $request->input('query');

        $filePath = storage_path('app/db_imports/' . $importId . '.json');

        if (!File::exists($filePath)) {
            return response()->json([
                'success' => false,
                'message' => 'Import session expired or invalid.',
            ], 404);
        }

        $statements = json_decode(File::get($filePath), true);

        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            try {
                DB::statement("SET SESSION sql_mode = ''");
            } catch (\Exception $e) {
            }

            DB::unprepared($query);
            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            // Update stored query in JSON file
            $statements[$index] = $query;
            File::put($filePath, json_encode($statements));

            return response()->json([
                'success' => true,
                'message' => 'Statement executed successfully!',
            ]);
        } catch (\Exception $e) {
            // Apply auto-solve in single statement executor too!
            $solveResult = $this->autoSolveQuery($query, $e->getMessage());
            if ($solveResult['success']) {
                $statements[$index] = $solveResult['solved_query'];
                File::put($filePath, json_encode($statements));

                return response()->json([
                    'success' => true,
                    'message' => 'Statement executed successfully via Auto-Solver!',
                ]);
            }

            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Intelligently auto-solve database syntax errors in imported queries.
     *
     * @param string $query
     * @param string $errorMessage
     * @return array
     */
    private function autoSolveQuery($query, $errorMessage)
    {
        $modifiedQuery = $query;
        $solved = false;

        // 1. Invalid date/datetime formats (like 0000-00-00 00:00:00 or 0000-00-00)
        if (stripos($errorMessage, 'Incorrect datetime value') !== false || stripos($errorMessage, 'Invalid datetime format') !== false) {
            // Replace zero date strings with clean, valid timestamps or NULL
            $modifiedQuery = str_replace("'0000-00-00 00:00:00'", "'1970-01-01 00:00:00'", $modifiedQuery);
            $modifiedQuery = str_replace("'0000-00-00'", "'1970-01-01'", $modifiedQuery);
            $solved = true;
        }

        // 2. Table already exists
        if (stripos($errorMessage, 'already exists') !== false && preg_match('/CREATE TABLE\s+(?:IF NOT EXISTS\s+)?`?([a-zA-Z0-9_]+)`?/i', $modifiedQuery, $matches)) {
            if (stripos($modifiedQuery, 'IF NOT EXISTS') === false) {
                $modifiedQuery = preg_replace('/CREATE TABLE\s+`?([a-zA-Z0-9_]+)`?/i', 'CREATE TABLE IF NOT EXISTS `$1`', $modifiedQuery);
                $solved = true;
            }
        }

        // 3. Duplicate key / duplicate primary entries
        if (stripos($errorMessage, 'Duplicate entry') !== false && stripos($modifiedQuery, 'INSERT INTO') !== false) {
            // Convert to INSERT IGNORE to automatically bypass duplicates
            $modifiedQuery = preg_replace('/INSERT INTO/i', 'INSERT IGNORE INTO', $modifiedQuery);
            $solved = true;
        }

        // 4. Missing default values
        if (stripos($errorMessage, "doesn't have a default value") !== false) {
            // Usually mitigated by setting sql_mode = '', but we confirm solved here
            $solved = true;
        }

        // 5. Redundant schema modifications (Multiple primary keys, duplicate indexes, duplicate columns)
        if (
            stripos($errorMessage, 'Multiple primary key defined') !== false ||
            stripos($errorMessage, 'Duplicate key name') !== false ||
            stripos($errorMessage, 'Duplicate column name') !== false ||
            stripos($errorMessage, 'check that column/key exists') !== false ||
            stripos($errorMessage, 'check that column exists') !== false
        ) {
            // The target schema constraint or column is already present or dropped. Safe to run a dummy query.
            $modifiedQuery = "SELECT 1;";
            $solved = true;
        }

        if ($solved) {
            try {
                DB::statement('SET FOREIGN_KEY_CHECKS=0');
                try {
                    DB::statement("SET SESSION sql_mode = ''");
                } catch (\Exception $e) {
                }

                DB::unprepared($modifiedQuery);
                DB::statement('SET FOREIGN_KEY_CHECKS=1');

                return [
                    'success' => true,
                    'solved_query' => $modifiedQuery,
                ];
            } catch (\Exception $ex) {
                // If secondary execution still fails, fall back to standard error report
            }
        }

        return [
            'success' => false,
        ];
    }
}
