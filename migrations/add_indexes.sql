-- =====================================================
-- EPES Database Indexes Migration
-- Run this against epes_db to improve query performance
-- =====================================================

-- User lookup indexes (login, forgot_password, reset_password)
CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
CREATE INDEX IF NOT EXISTS idx_users_reset_token ON users(reset_token);
CREATE INDEX IF NOT EXISTS idx_employee_list_email ON employee_list(email);
CREATE INDEX IF NOT EXISTS idx_employee_list_reset_token ON employee_list(reset_token);
CREATE INDEX IF NOT EXISTS idx_evaluator_list_email ON evaluator_list(email);
CREATE INDEX IF NOT EXISTS idx_evaluator_list_reset_token ON evaluator_list(reset_token);

-- Login audit trail (rate limiting, security forensics)
CREATE INDEX IF NOT EXISTS idx_audit_ip_status ON login_audit_trail(ip_address, login_status, login_time);
CREATE INDEX IF NOT EXISTS idx_audit_user ON login_audit_trail(user_id);
CREATE INDEX IF NOT EXISTS idx_audit_created ON login_audit_trail(login_time);

-- Ratings and evaluation lookups
CREATE INDEX IF NOT EXISTS idx_ratings_employee ON ratings(employee_id);
CREATE INDEX IF NOT EXISTS idx_ratings_task ON ratings(task_id);
CREATE INDEX IF NOT EXISTS idx_ratings_period ON ratings(rating_period);

-- Task progress lookups
CREATE INDEX IF NOT EXISTS idx_task_progress_faculty ON task_progress(faculty_id);
CREATE INDEX IF NOT EXISTS idx_task_progress_task ON task_progress(task_id);
CREATE INDEX IF NOT EXISTS idx_task_progress_date ON task_progress(date_created);

-- Task list lookups (created_by already indexed)
CREATE INDEX IF NOT EXISTS idx_task_list_active ON task_list(is_active);

-- Comments
CREATE INDEX IF NOT EXISTS idx_comments_employee ON comments(employee_id);
CREATE INDEX IF NOT EXISTS idx_comments_rater ON comments(rater_id);

-- Renewal recommendations
CREATE INDEX IF NOT EXISTS idx_renewal_faculty ON renewal_recommendations(faculty_id);

-- Department/designation lookups
CREATE INDEX IF NOT EXISTS idx_employee_dept ON employee_list(department_id);
CREATE INDEX IF NOT EXISTS idx_employee_position ON employee_list(position_id);
CREATE INDEX IF NOT EXISTS idx_employee_designation ON employee_list(designation_id);
CREATE INDEX IF NOT EXISTS idx_employee_evaluator ON employee_list(evaluator_id);

-- MOV (Means of Verification)
CREATE INDEX IF NOT EXISTS idx_mov_faculty ON mov_uploads(faculty_id);
