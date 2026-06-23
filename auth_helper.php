<?php
/**
 * Auth helper - shared access-control functions
 * Place in project root and require where needed.
 */

if (!function_exists('require_login')) {
    function require_login() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['login_id'])) {
            header('Location: login.php');
            exit;
        }
    }
}

if (!function_exists('current_login_type')) {
    function current_login_type() {
        return $_SESSION['login_type'] ?? -1;
    }
}

if (!function_exists('current_user_id')) {
    function current_user_id() {
        return $_SESSION['login_id'] ?? 0;
    }
}

if (!function_exists('require_role')) {
    /**
     * Allow access only if the logged-in user's login_type is in $allowed.
     * Otherwise show an access-denied message and exit.
     */
    function require_role(array $allowed) {
        require_login();
        if (!in_array(current_login_type(), $allowed, true)) {
            echo '<div class="col-lg-12"><div class="alert alert-danger">Access denied. You do not have permission to view this page.</div></div>';
            exit;
        }
    }
}

if (!function_exists('is_admin')) {
    function is_admin() {
        return current_login_type() === 2;
    }
}

if (!function_exists('is_evaluator')) {
    /**
     * True if login_type=1 (legacy evaluator) OR login_type=0 with is_evaluator session flag
     */
    function is_evaluator() {
        if (current_login_type() === 1) return true;
        return current_login_type() === 0 && !empty($_SESSION['is_evaluator']);
    }
}

if (!function_exists('is_faculty')) {
    function is_faculty() {
        return current_login_type() === 0;
    }
}

if (!function_exists('is_dean')) {
    /**
     * True for: login_type=1 evaluators with type=1, OR login_type=0 with evaluator_role='dean'
     */
    function is_dean($conn = null) {
        if (current_login_type() === 1) {
            // Legacy evaluator — check evaluator_list.type
            $eval_id = current_user_id();
            $stmt = $conn->prepare("SELECT type FROM evaluator_list WHERE id = ? LIMIT 1");
            $stmt->bind_param('i', $eval_id);
            $stmt->execute();
            $stmt->bind_result($type);
            $stmt->fetch();
            $stmt->close();
            return (int)$type === 1;
        }
        // New merged faculty — check session
        return current_login_type() === 0 && ($_SESSION['evaluator_role'] ?? '') === 'dean';
    }
}

if (!function_exists('is_program_head')) {
    /**
     * True for: login_type=1 evaluators with type=0, OR login_type=0 with evaluator_role='dept_head'
     */
    function is_program_head($conn = null) {
        if (current_login_type() === 1) {
            // Legacy evaluator — check evaluator_list.type
            $eval_id = current_user_id();
            $stmt = $conn->prepare("SELECT type FROM evaluator_list WHERE id = ? LIMIT 1");
            $stmt->bind_param('i', $eval_id);
            $stmt->execute();
            $stmt->bind_result($type);
            $stmt->fetch();
            $stmt->close();
            return (int)$type === 0;
        }
        // New merged faculty — check session
        return current_login_type() === 0 && ($_SESSION['evaluator_role'] ?? '') === 'dept_head';
    }
}

if (!function_exists('user_type_label')) {
    function user_type_label($login_type = null) {
        $t = $login_type ?? current_login_type();
        if ((int)$t === 0 && !empty($_SESSION['is_evaluator'])) {
            $er = $_SESSION['evaluator_role'] ?? '';
            return match ($er) {
                'dean' => 'Dean',
                'vp' => 'Vice President',
                'director' => 'Director',
                'dept_head' => 'Department Head',
                default => 'Faculty'
            };
        }
        return match ((int)$t) {
            0 => 'Faculty',
            1 => 'Evaluator',
            2 => 'Administrator',
            default => 'Unknown'
        };
    }
}

if (!function_exists('evaluator_subtype_label')) {
    function evaluator_subtype_label($conn = null) {
        if (is_dean($conn)) return 'Dean';
        if (is_program_head($conn)) return 'Program Head / Immediate Supervisor';
        return user_type_label();
    }
}
