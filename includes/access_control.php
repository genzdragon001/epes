<?php
/**
 * Shared access control — included by faculty_list.php, employee_eval_status.php,
 * evaluation.php, and any page that needs role-based faculty scoping.
 *
 * Requires: $conn (from db_connect.php), $_SESSION
 * Sets: $is_admin, $is_dean, $is_dept_head, $is_vp, $dept_id, $eval_id,
 *       $eval_list_id (mapped evaluator_list.id for VP evaluator_id lookup)
 *
 * Convention:
 *   login_type 2 = admin (sees all)
 *   login_type 1 = legacy evaluator (evaluator_list.type: 1=dean, 0=dept_head)
 *   login_type 0 + is_evaluator = merged faculty-evaluator (session-based role)
 */

if (!function_exists('epes_resolve_access')) {
    function epes_resolve_access($conn) {
        $login_type = $_SESSION['login_type'] ?? -1;
        $eval_id    = intval($_SESSION['login_id'] ?? 0);
        $is_admin   = ($login_type == 2);
        $is_dean    = false;
        $is_dept_head = false;
        $is_vp      = false;
        $dept_id    = 0;
        $eval_list_id = $eval_id; // for VP evaluator_id mapping

        if (!$is_admin) {
            if (!empty($_SESSION['is_evaluator'])) {
                // Merged faculty-evaluator
                $eval_role = $_SESSION['evaluator_role'] ?? '';
                $is_dean      = ($eval_role === 'dean');
                $is_dept_head = ($eval_role === 'dept_head');
                $is_vp        = ($eval_role === 'vp');
                $stmt = $conn->prepare("SELECT department_id FROM employee_list WHERE id = ?");
                $stmt->bind_param("i", $eval_id);
                $stmt->execute();
                $stmt->bind_result($dept_id);
                $stmt->fetch();
                $stmt->close();
            } else {
                // Legacy evaluator (login_type=1)
                $eval_desig_id = 0;
                $stmt_type = $conn->prepare("SELECT type, department_id, designation_id FROM evaluator_list WHERE id = ?");
                $stmt_type->bind_param("i", $eval_id);
                $stmt_type->execute();
                $stmt_type->bind_result($eval_type, $dept_id, $eval_desig_id);
                $stmt_type->fetch();
                $stmt_type->close();

                $is_dean      = ($eval_type == 1);
                $is_dept_head = ($eval_type == 0);

                // Override: VP designations are not dept heads — they evaluate
                // only faculty explicitly assigned via evaluator_id.
                $vp_desigs = [4, 9, 10, 18, 19]; // VPAF, VPAA, VPREI (both ID schemes)
                $effective_desig = intval($eval_desig_id ?? 0);
                if ($effective_desig === 0) {
                    $eval_email = $conn->real_escape_string($_SESSION['login_email'] ?? '');
                    $ed_q = $conn->query("SELECT designation_id FROM employee_list WHERE email = '$eval_email' LIMIT 1");
                    if ($ed_q && $ed_q->num_rows > 0) {
                        $effective_desig = intval($ed_q->fetch_assoc()['designation_id']);
                    }
                }
                if (in_array($effective_desig, $vp_desigs)) {
                    $is_dept_head = false;
                    $is_dean      = false;
                    $is_vp        = true;
                }
            }

            // For VP: map session login_id to evaluator_list.id that
            // employee_list.evaluator_id references.
            if ($is_vp) {
                $eval_email = $conn->real_escape_string($_SESSION['login_email'] ?? '');
                $eval_map_q = $conn->query("SELECT id FROM evaluator_list WHERE email = '$eval_email' LIMIT 1");
                if ($eval_map_q && $eval_map_q->num_rows > 0) {
                    $eval_list_id = intval($eval_map_q->fetch_assoc()['id']);
                }
            }
        }

        return compact('login_type', 'eval_id', 'is_admin', 'is_dean',
            'is_dept_head', 'is_vp', 'dept_id', 'eval_list_id');
    }
}

// Auto-extract on include (only if $conn is available — pages include
// db_connect.php before this file, so $conn is always set in practice).
if (isset($conn) && $conn instanceof mysqli) {
    extract(epes_resolve_access($conn));
}