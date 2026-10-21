{{-- 
    Helper to determine if user can view price
    Sets $canViewPrice variable based on user permissions
--}}

@php
    $canViewPrice = false;
    $currentGuard = $modules['currentGuard'] ?? null;
    $authLoginUserDetail = $modules['authLoginUserDetail'] ?? null;
    $parent_type_id = isset($modules['parent_type_id']) ? (int) $modules['parent_type_id'] : 0;

    if ($currentGuard === 'admin_software') {
        // Company Admin or Super Admin
        $canViewPrice = true;
    } elseif ($currentGuard === 'team_person' && $authLoginUserDetail) {
        // Check if team_person has parent_type = 0
        if ($parent_type_id === 0) {
            $canViewPrice = true;
        }
    }
@endphp

