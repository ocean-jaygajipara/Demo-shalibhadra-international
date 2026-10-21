@extends('software.layout.app')

@section('title', 'Database Import Utility')

@section('page_leavel_style')
    <link rel="stylesheet" href="{{ asset('software/vendor/css/pages/page-auth.css') }}" />
    <style>
        /* Sleek animated liquid gradient background for authentication wrapper */
        .authentication-wrapper {
            background: linear-gradient(-45deg, #f5f3ff, #eef2ff, #f0fdfa, #fdf2f8) !important;
            background-size: 400% 400% !important;
            animation: fluidGradient 15s ease infinite !important;
            position: relative;
            overflow: hidden;
            min-height: 100vh;
        }

        @keyframes fluidGradient {
            0% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }

            100% {
                background-position: 0% 50%;
            }
        }

        /* Ambient floating glassmorphism shapes in background */
        .bg-blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            z-index: 0;
            opacity: 0.45;
            animation: floatBlob 18s infinite alternate ease-in-out;
        }

        .bg-blob-1 {
            width: 350px;
            height: 350px;
            background: #c084fc;
            top: 10%;
            left: 15%;
        }

        .bg-blob-2 {
            width: 400px;
            height: 400px;
            background: #818cf8;
            bottom: 10%;
            right: 15%;
            animation-delay: -6s;
        }

        @keyframes floatBlob {
            0% {
                transform: translate(0px, 0px) scale(1);
            }

            50% {
                transform: translate(30px, -40px) scale(1.1);
            }

            100% {
                transform: translate(-20px, 20px) scale(0.9);
            }
        }

        /* Glassmorphic card styling with glowing shifting ambient border */
        .glassmorphic-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 20px 50px rgba(115, 103, 240, 0.08);
            position: relative;
            z-index: 1;
            transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
        }

        .glassmorphic-card::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(45deg, #7367f0, #c084fc, #38bdf8, #7367f0);
            background-size: 400%;
            z-index: -1;
            filter: blur(12px);
            opacity: 0.15;
            border-radius: 26px;
            animation: rotateGlow 12s linear infinite;
            transition: opacity 0.3s ease-in-out;
        }

        .glassmorphic-card:hover::before {
            opacity: 0.28;
        }

        @keyframes rotateGlow {
            0% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }

            100% {
                background-position: 0% 50%;
            }
        }

        .drag-drop-zone {
            border: 2px dashed #7367f0;
            background: rgba(115, 103, 240, 0.03);
            border-radius: 16px;
            padding: 55px 25px;
            text-align: center;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            cursor: pointer;
            position: relative;
        }

        .drag-drop-zone:hover,
        .drag-drop-zone.dragover {
            background: rgba(115, 103, 240, 0.07);
            border-color: #5e50eb;
            transform: scale(1.015) translateY(-2px);
            box-shadow: 0 8px 25px rgba(115, 103, 240, 0.1);
        }

        .dark-terminal {
            background: #0c0c0e;
            color: #00e676;
            font-family: 'Consolas', 'Courier New', Courier, monospace;
            border-radius: 14px;
            padding: 22px;
            max-height: 280px;
            min-height: 145px;
            overflow-y: auto;
            box-shadow: inset 0 0 20px rgba(0, 0, 0, 0.95);
            font-size: 0.9rem;
            line-height: 1.6;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .dark-terminal .error-log {
            color: #ff3d00;
            text-shadow: 0 0 2px rgba(255, 61, 0, 0.25);
        }

        .dark-terminal .info-log {
            color: #00e5ff;
            text-shadow: 0 0 2px rgba(0, 229, 255, 0.25);
        }

        .dark-terminal .success-log {
            color: #00e676;
            text-shadow: 0 0 2px rgba(0, 230, 118, 0.25);
        }

        .progress-bar-container {
            height: 14px;
            background-color: #f1f3f5;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.08);
        }

        .progress-bar-fill {
            height: 100%;
            background-image: linear-gradient(45deg, rgba(255, 255, 255, 0.15) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, 0.15) 50%, rgba(255, 255, 255, 0.15) 75%, transparent 75%, transparent);
            background-size: 1rem 1rem;
            background-color: #7367f0;
            width: 0%;
            transition: width 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            animation: progress-bar-stripes 1s linear infinite;
        }

        @keyframes progress-bar-stripes {
            from {
                background-position: 1rem 0;
            }

            to {
                background-position: 0 0;
            }
        }

        /* Sleek Animated Glowing Loading Line */
        .animated-loading-line {
            height: 6px;
            width: 100%;
            position: relative;
            overflow: hidden;
            background-color: rgba(115, 103, 240, 0.08);
            border-radius: 4px;
        }

        .animated-loading-line::before {
            content: '';
            position: absolute;
            left: -50%;
            height: 100%;
            width: 50%;
            background: linear-gradient(90deg, transparent, #7367f0, #c084fc, #7367f0, transparent);
            animation: loading-bar-glide 1.2s infinite linear;
        }

        @keyframes loading-bar-glide {
            0% {
                left: -50%;
            }

            100% {
                left: 100%;
            }
        }

        .glow-button {
            transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
            border-radius: 10px;
        }

        .glow-button:hover:not(:disabled) {
            box-shadow: 0 8px 25px rgba(115, 103, 240, 0.38);
            transform: translateY(-2px);
        }

        .glow-button-success {
            transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
            border-radius: 10px;
        }

        .glow-button-success:hover:not(:disabled) {
            box-shadow: 0 8px 25px rgba(40, 199, 111, 0.38);
            transform: translateY(-2px);
        }

        .font-monospace {
            font-family: 'Consolas', 'Courier New', Courier, monospace !important;
        }

        .card-glow-red {
            border: 1px solid rgba(255, 61, 0, 0.3) !important;
            box-shadow: 0 6px 25px rgba(255, 61, 0, 0.12) !important;
        }

        .pulse-lock {
            animation: lockPulse 2s infinite ease-in-out;
        }

        @keyframes lockPulse {
            0% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(115, 103, 240, 0.4);
            }

            70% {
                transform: scale(1.06);
                box-shadow: 0 0 0 16px rgba(115, 103, 240, 0);
            }

            100% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(115, 103, 240, 0);
            }
        }

        .fade-in-slide-up {
            animation: fadeInUp 0.7s cubic-bezier(0.19, 1, 0.22, 1) forwards;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(40px) scale(0.97);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
    </style>
@endsection

@section('content')
    <div class="authentication-wrapper authentication-cover authentication-bg d-flex align-items-center justify-content-center py-5"
        style="min-height: 100vh;">
        <!-- Glowing background design blobs -->
        <div class="bg-blob bg-blob-1"></div>
        <div class="bg-blob bg-blob-2"></div>

        <div class="container d-flex justify-content-center" style="position: relative; z-index: 2;">
            <div class="col-12 col-lg-8 col-md-10">

                @if(!$unlocked)
                    <!-- STEP 1: Beautiful Password Unlock Card -->
                    <div id="password-lock-card"
                        class="card glassmorphic-card p-4 shadow-lg border-primary mx-auto fade-in-slide-up"
                        style="max-width: 480px;">
                        <div class="card-body text-center">
                            <div class="mb-4">
                                <div class="d-inline-flex align-items-center justify-content-center bg-label-primary rounded-circle p-3 mb-3 pulse-lock"
                                    style="width: 76px; height: 76px;">
                                    <i class="ti ti-lock-square-rounded fs-1 text-primary"
                                        style="font-size: 2.5rem !important;"></i>
                                </div>
                                <h3 class="fw-bold text-dark mb-1">Security Access Verification</h3>
                                <p class="text-muted small">Please enter the security verification key to unlock the Database
                                    Import Portal.</p>
                            </div>

                            <form id="password-form" class="mb-3">
                                <div class="mb-3 text-start">
                                    <label class="form-label fw-semibold text-dark" for="security_password">Security Key</label>
                                    <div class="input-group input-group-merge">
                                        <input type="password" id="security_password" class="form-control"
                                            placeholder="••••••••" required autofocus style="padding: 10px 14px;" />
                                        <span class="input-group-text cursor-pointer" id="toggle-password-visibility"><i
                                                class="ti ti-eye-off"></i></span>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary w-100 glow-button py-25 fs-5 mb-3"
                                    id="verify-pw-btn">
                                    <i class="ti ti-key me-1"></i> Verify & Access
                                </button>

                                <div id="password-loading" class="d-none mt-2">
                                    <div class="animated-loading-line mb-2"></div>
                                    <div class="text-center text-primary fw-semibold small">Validating Security Key...</div>
                                </div>
                            </form>

                            <a href="{{ route('software.login') }}" class="text-muted small"><i
                                    class="ti ti-arrow-left me-1"></i> Return to Login</a>
                        </div>
                    </div>
                @endif

                <!-- STEP 2: Main Import Utility Interface (Faded in after unlocking) -->
                <div id="main-import-container"
                    class="card glassmorphic-card p-4 {{ !$unlocked ? 'd-none' : 'fade-in-slide-up' }}">
                    <div class="card-body">
                        <!-- Header -->
                        <div
                            class="d-flex align-items-center justify-content-between mb-4 border-bottom pb-3 flex-wrap gap-3">
                            <div class="d-flex align-items-center gap-2">
                                <i class="ti ti-database-import fs-2 text-primary"></i>
                                <h3 class="mb-0 fw-bold text-dark">Database Import Utility</h3>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="{{ route('software.db-import.backup') }}"
                                    class="btn btn-success glow-button-success d-flex align-items-center gap-1"
                                    id="backup-db-btn" title="Download dynamic database SQL backup file">
                                    <i class="ti ti-download fs-5"></i> 💾 Backup Old DB
                                </a>
                                <a href="{{ route('software.login') }}"
                                    class="btn btn-label-secondary d-flex align-items-center gap-1">
                                    <i class="ti ti-arrow-left fs-5"></i> Back to Login
                                </a>
                            </div>
                        </div>

                        <!-- Upload Area & Guidelines -->
                        <div id="upload-section">
                            <p class="text-muted mb-4">
                                Download a backup of the current database using the <strong>💾 Backup Old DB</strong> button
                                above to secure your old records, then upload your new SQL dump file below. The import will
                                run in dynamic, non-blocking chunks of <strong>20 SQL statements</strong>.
                            </p>

                            <form id="upload-form" enctype="multipart/form-data">
                                <div class="drag-drop-zone mb-4" id="drop-zone">
                                    <input type="file" id="sql_file" name="sql_file" accept=".sql" class="d-none" />
                                    <div class="py-3">
                                        <i class="ti ti-cloud-upload text-primary fs-1 mb-3 d-block animate__animated animate__bounce"
                                            style="font-size: 3.5rem !important; animation-duration: 2s; animation-iteration-count: infinite;"></i>
                                        <h5 class="fw-semibold text-dark mb-1">Drag and drop your SQL file here</h5>
                                        <p class="text-muted small mb-3">or click to browse from your computer</p>
                                        <span class="badge bg-label-primary font-monospace">Supports .sql files</span>
                                    </div>
                                </div>

                                <div
                                    class="selected-file-card d-none mb-4 p-3 border rounded d-flex align-items-center justify-content-between bg-light animate__animated animate__fadeIn">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="ti ti-file-text fs-3 text-primary"></i>
                                        <div>
                                            <h6 class="mb-0 fw-semibold text-dark" id="file-name">filename.sql</h6>
                                            <small class="text-muted" id="file-size">0 KB</small>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-icon btn-text-danger" id="remove-file-btn">
                                        <i class="ti ti-x fs-4"></i>
                                    </button>
                                </div>

                                <button type="submit" class="btn btn-primary w-100 glow-button py-25 fs-5"
                                    id="start-parse-btn" disabled>
                                    <i class="ti ti-player-play-filled me-1"></i> Parse & Prepare SQL File
                                </button>

                                <div id="parse-loading" class="d-none mt-3">
                                    <div class="animated-loading-line mb-2"></div>
                                    <div
                                        class="text-center text-primary fw-semibold small animate__animated animate__pulse animate__infinite">
                                        <i class="ti ti-loader ti-spin me-1 fs-5"></i> Uploading & parsing large SQL file...
                                        Please wait.
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Live Progress Bar & System Terminal Logs -->
                        <div id="progress-section" class="d-none">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="fw-semibold text-dark fs-5" id="import-status-text">Importing
                                    statements...</span>
                                <span class="fw-bold text-primary fs-5" id="progress-percent">0%</span>
                            </div>

                            <div class="progress-bar-container mb-4">
                                <div class="progress-bar-fill" id="progress-fill"></div>
                            </div>

                            <div id="chunk-loading-line" class="animated-loading-line mb-4 d-none"></div>

                            <div class="row text-center mb-4">
                                <div class="col-4 border-end">
                                    <small class="text-muted d-block">Processed</small>
                                    <span class="fw-semibold text-dark fs-4 animate__animated animate__flash"
                                        id="stats-processed">0</span>
                                </div>
                                <div class="col-4 border-end">
                                    <small class="text-muted d-block">Total Statements</small>
                                    <span class="fw-semibold text-dark fs-4" id="stats-total">0</span>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted d-block">Remaining</small>
                                    <span class="fw-semibold text-dark fs-4" id="stats-remaining">0</span>
                                </div>
                            </div>

                            <h6 class="fw-semibold text-dark mb-2 d-flex align-items-center gap-1">
                                <i class="ti ti-terminal fs-5 text-primary"></i> Live Import Console
                            </h6>
                            <div class="dark-terminal mb-4" id="console-terminal">
                                <div class="info-log">[SYSTEM] Initializing console... Ready.</div>
                            </div>
                        </div>

                        <!-- Interactive Exception / Syntax Auto-Solver -->
                        <div id="error-section"
                            class="d-none mb-4 card card-glow-red p-4 bg-light-danger border-danger animate__animated animate__shakeX">
                            <div class="d-flex align-items-start gap-2 mb-3">
                                <i class="ti ti-alert-triangle-filled fs-3 text-danger mt-1"></i>
                                <div>
                                    <h5 class="fw-bold text-danger mb-1">SQL Statement Execution Failed</h5>
                                    <p class="text-muted mb-0 small">An error occurred at statement index <strong
                                            id="failed-statement-index">#0</strong>.</p>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold text-dark">Database Error Message</label>
                                <div class="p-3 border rounded bg-white font-monospace text-danger small shadow-sm"
                                    id="error-message-text" style="white-space: pre-wrap; word-break: break-all;">
                                    MySQL Error Code...
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <label class="form-label fw-semibold text-dark mb-0">SQL Query (You can edit and correct
                                        it below)</label>
                                    <span class="badge bg-label-danger font-monospace">Editable Code Console</span>
                                </div>
                                <textarea class="form-control font-monospace p-3 bg-dark text-white border-0 shadow-sm"
                                    id="failed-query-textarea" rows="6"
                                    style="border-radius: 8px; font-size: 0.9rem; line-height: 1.5; color: #fff !important;"></textarea>
                            </div>

                            <div class="d-flex gap-2">
                                <input type="hidden" id="failed-index-hidden" />
                                <button type="button" class="btn btn-success flex-grow-1 glow-button"
                                    id="retry-execute-btn">
                                    <i class="ti ti-player-play me-1"></i> Execute Corrected SQL & Resume
                                </button>
                                <button type="button" class="btn btn-warning glow-button" id="skip-statement-btn">
                                    <i class="ti ti-player-skip-forward me-1"></i> Skip Statement & Resume
                                </button>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection

@section('page_leavel_script')
    <script>
        $(document).ready(function () {
            // Setup CSRF Tokens for all AJAX requests
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });

            // Password Toggle Visibility
            $('#toggle-password-visibility').on('click', function () {
                const passField = $('#security_password');
                const icon = $(this).find('i');
                if (passField.attr('type') === 'password') {
                    passField.attr('type', 'text');
                    icon.removeClass('ti-eye-off').addClass('ti-eye');
                } else {
                    passField.attr('type', 'password');
                    icon.removeClass('ti-eye').addClass('ti-eye-off');
                }
            });

            // Password Verification AJAX
            $('#password-form').on('submit', function (e) {
                e.preventDefault();
                const password = $('#security_password').val();

                $('#password-loading').removeClass('d-none');
                $('#verify-pw-btn').attr('disabled', true);

                $.ajax({
                    url: "{{ route('software.db-import.verify-password') }}",
                    type: "POST",
                    data: {
                        password: password
                    },
                    success: function (response) {
                        $('#password-loading').addClass('d-none');
                        $('#verify-pw-btn').removeAttr('disabled');
                        if (response.success) {
                            toastr.success('Verification Key accepted! Opening portal...');

                            // Animate transition smoothly
                            $('#password-lock-card').addClass('animate__animated animate__fadeOutDown');
                            setTimeout(function () {
                                $('#password-lock-card').hide();
                                $('#main-import-container').removeClass('d-none').addClass('fade-in-slide-up').show();
                            }, 400);
                        } else {
                            toastr.error(response.message || 'Incorrect password.');
                            $('#security_password').val('').focus();
                            $('#password-lock-card').addClass('animate__animated animate__shakeX');
                            setTimeout(function () {
                                $('#password-lock-card').removeClass('animate__animated animate__shakeX');
                            }, 500);
                        }
                    },
                    error: function () {
                        $('#password-loading').addClass('d-none');
                        $('#verify-pw-btn').removeAttr('disabled');
                        toastr.error('Authentication request failed. Please retry.');
                    }
                });
            });

            const dropZone = $('#drop-zone');
            const fileInput = $('#sql_file');
            const selectedFileCard = $('.selected-file-card');
            const fileNameSpan = $('#file-name');
            const fileSizeSpan = $('#file-size');
            const removeFileBtn = $('#remove-file-btn');
            const startParseBtn = $('#start-parse-btn');
            const uploadForm = $('#upload-form');

            // Drag and drop events
            dropZone.on('click', function () {
                fileInput.click();
            });

            dropZone.on('dragover', function (e) {
                e.preventDefault();
                dropZone.addClass('dragover');
            });

            dropZone.on('dragleave', function () {
                dropZone.removeClass('dragover');
            });

            dropZone.on('drop', function (e) {
                e.preventDefault();
                dropZone.removeClass('dragover');
                const files = e.originalEvent.dataTransfer.files;
                if (files.length > 0) {
                    fileInput[0].files = files;
                    handleFileSelection();
                }
            });

            fileInput.on('change', function () {
                handleFileSelection();
            });

            removeFileBtn.on('click', function () {
                fileInput.val('');
                selectedFileCard.addClass('d-none');
                dropZone.removeClass('d-none');
                startParseBtn.attr('disabled', true);
            });

            function handleFileSelection() {
                const file = fileInput[0].files[0];
                if (file) {
                    // Validate extension
                    const ext = file.name.split('.').pop().toLowerCase();
                    if (ext !== 'sql') {
                        toastr.error('Invalid file type! Please upload a .sql file.');
                        fileInput.val('');
                        return;
                    }

                    fileNameSpan.text(file.name);
                    fileSizeSpan.text(formatBytes(file.size));
                    selectedFileCard.removeClass('d-none');
                    dropZone.addClass('d-none');
                    startParseBtn.removeAttr('disabled');
                }
            }

            function formatBytes(bytes, decimals = 2) {
                if (bytes === 0) return '0 Bytes';
                const k = 1024;
                const dm = decimals < 0 ? 0 : decimals;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
            }

            // AJAX parsing and state variables
            let importId = null;
            let totalStatements = 0;
            let currentOffset = 0;
            const chunkSize = 20;

            uploadForm.on('submit', function (e) {
                e.preventDefault();

                const formData = new FormData();
                formData.append('sql_file', fileInput[0].files[0]);

                logTerminal('[SYSTEM] Uploading and parsing SQL file...', 'info');

                // Show parsing animation
                $('#parse-loading').removeClass('d-none');
                startParseBtn.attr('disabled', true);

                $.ajax({
                    url: "{{ route('software.db-import.upload') }}",
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (response) {
                        $('#parse-loading').addClass('d-none');
                        if (response.success) {
                            importId = response.import_id;
                            totalStatements = response.total_statements;

                            // Hide upload form, show terminal progress
                            $('#upload-section').addClass('d-none');
                            $('#progress-section').removeClass('d-none').addClass('animate__animated animate__fadeIn');

                            logTerminal(`[SUCCESS] File successfully parsed. Found ${totalStatements} SQL statements.`, 'success');
                            logTerminal(`[SYSTEM] Starting database import in chunks of ${chunkSize} statements...`, 'info');

                            $('#stats-total').text(totalStatements);

                            startChunkImport();
                        } else {
                            startParseBtn.removeAttr('disabled');
                            toastr.error(response.message || 'Error occurred while parsing the file.');
                        }
                    },
                    error: function (xhr) {
                        $('#parse-loading').addClass('d-none');
                        startParseBtn.removeAttr('disabled');
                        const errMsg = xhr.responseJSON?.message || 'Failed to upload and parse SQL file.';
                        toastr.error(errMsg);
                        logTerminal(`[ERROR] Parsing failed: ${errMsg}`, 'error');
                    }
                });
            });

            function startChunkImport() {
                $('#chunk-loading-line').removeClass('d-none');
                executeChunk();
            }

            function executeChunk() {
                if (currentOffset >= totalStatements) {
                    completeImport();
                    return;
                }

                logTerminal(`[SYSTEM] Executing statements ${currentOffset + 1} to ${Math.min(currentOffset + chunkSize, totalStatements)}...`, 'info');

                $.ajax({
                    url: "{{ route('software.db-import.execute-chunk') }}",
                    type: "POST",
                    data: {
                        import_id: importId,
                        offset: currentOffset,
                        chunk_size: chunkSize
                    },
                    success: function (response) {
                        if (response.success) {
                            if (response.completed) {
                                completeImport();
                            } else {
                                currentOffset = response.next_offset;
                                updateProgressVisuals();
                                logTerminal(`[SUCCESS] Statements executed successfully.`, 'success');
                                setTimeout(executeChunk, 200); // 200ms delay for smooth UI feel
                            }
                        } else {
                            showErrorSolver(response);
                        }
                    },
                    error: function (xhr) {
                        const errMsg = xhr.responseJSON?.message || 'Connection or execution error occurred.';
                        showErrorSolver({
                            error: errMsg,
                            query: 'Chunk batch execution failed',
                            failed_index: currentOffset
                        });
                    }
                });
            }

            function updateProgressVisuals() {
                const percent = Math.round((currentOffset / totalStatements) * 100);
                $('#progress-fill').css('width', percent + '%');
                $('#progress-percent').text(percent + '%');
                $('#stats-processed').text(currentOffset);
                $('#stats-remaining').text(totalStatements - currentOffset);
            }

            function completeImport() {
                $('#chunk-loading-line').addClass('d-none');
                $('#progress-fill').css('width', '100%');
                $('#progress-percent').text('100%');
                $('#stats-processed').text(totalStatements);
                $('#stats-remaining').text(0);

                $('#import-status-text').html('<span class="text-success fw-bold"><i class="ti ti-circle-check-filled me-1"></i>Import Completed Successfully!</span>');
                logTerminal('[SUCCESS] All SQL statements imported into active database successfully!', 'success');

                Swal.fire({
                    title: "Import Success",
                    text: "Your SQL database dump has been fully and cleanly imported!",
                    icon: "success",
                    confirmButtonText: "Back to Login Page",
                    customClass: {
                        confirmButton: 'btn btn-primary waves-effect waves-light animate__animated animate__pulse animate__infinite',
                    },
                    allowOutsideClick: false,
                    allowEscapeKey: false
                }).then(function () {
                    window.location.href = "{{ route('software.login') }}";
                });
            }

            function showErrorSolver(response) {
                $('#chunk-loading-line').addClass('d-none');
                logTerminal(`[CRITICAL ERROR] Execution failed at statement #${response.failed_index + 1}.`, 'error');

                $('#failed-statement-index').text(`#${response.failed_index + 1}`);
                $('#error-message-text').text(response.error);
                $('#failed-query-textarea').val(response.query);
                $('#failed-index-hidden').val(response.failed_index);

                // Show error card smoothly
                $('#error-section').removeClass('d-none');

                // Scroll to error card
                $('html, body').animate({
                    scrollTop: $('#error-section').offset().top - 80
                }, 400);
            }

            // Retry execute manual SQL
            $('#retry-execute-btn').on('click', function () {
                const correctedQuery = $('#failed-query-textarea').val();
                const failedIndex = $('#failed-index-hidden').val();

                if (!correctedQuery.trim()) {
                    toastr.warning('Please enter a valid SQL query to execute.');
                    return;
                }

                logTerminal(`[SYSTEM] Retrying custom execution for statement #${parseInt(failedIndex) + 1}...`, 'info');
                $('#chunk-loading-line').removeClass('d-none');

                $.ajax({
                    url: "{{ route('software.db-import.execute-single') }}",
                    type: "POST",
                    data: {
                        import_id: importId,
                        index: failedIndex,
                        query: correctedQuery
                    },
                    success: function (response) {
                        if (response.success) {
                            logTerminal(`[SUCCESS] Manual correction executed successfully for statement #${parseInt(failedIndex) + 1}!`, 'success');
                            toastr.success('SQL Statement corrected and executed successfully.');

                            // Hide error section, move offset forward, and resume chunk import
                            $('#error-section').addClass('d-none');
                            currentOffset = parseInt(failedIndex) + 1;
                            updateProgressVisuals();
                            executeChunk();
                        } else {
                            $('#chunk-loading-line').addClass('d-none');
                            toastr.error(response.error || 'Execution failed. Please double check SQL syntax.');
                            $('#error-message-text').text(response.error);
                        }
                    },
                    error: function (xhr) {
                        $('#chunk-loading-line').addClass('d-none');
                        const errMsg = xhr.responseJSON?.message || 'Server error while executing single statement.';
                        toastr.error(errMsg);
                    }
                });
            });

            // Skip failed SQL statement
            $('#skip-statement-btn').on('click', function () {
                const failedIndex = $('#failed-index-hidden').val();

                Swal.fire({
                    title: "Skip SQL Statement?",
                    text: `Are you sure you want to skip statement #${parseInt(failedIndex) + 1}? Skips are recommended only for minor non-structural errors.`,
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonText: "Yes, Skip it",
                    cancelButtonText: "Cancel",
                    customClass: {
                        confirmButton: 'btn btn-warning waves-effect waves-light me-2',
                        cancelButton: 'btn btn-label-secondary waves-effect waves-light'
                    },
                    buttonsStyling: false
                }).then(function (result) {
                    if (result.isConfirmed) {
                        logTerminal(`[WARNING] Skipped statement #${parseInt(failedIndex) + 1} by user directive.`, 'info');

                        // Hide error, increment offset, and continue
                        $('#error-section').addClass('d-none');
                        currentOffset = parseInt(failedIndex) + 1;
                        updateProgressVisuals();
                        executeChunk();
                    }
                });
            });

            function logTerminal(message, type = 'info') {
                const term = $('#console-terminal');
                let className = '';
                if (type === 'success') className = 'success-log';
                else if (type === 'error') className = 'error-log';
                else if (type === 'info') className = 'info-log';

                const timestamp = new Date().toLocaleTimeString();
                term.append(`<div class="${className}">[${timestamp}] ${escapeHtml(message)}</div>`);

                // Auto scroll to bottom of terminal
                term.scrollTop(term[0].scrollHeight);
            }

            function escapeHtml(text) {
                return text
                    .replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/"/g, "&quot;")
                    .replace(/'/g, "&#039;");
            }
        });
    </script>
@endsection