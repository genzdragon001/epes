-- Migration: Add TER and Instruction sub-items under Instructions
-- This splits the original 'instructions' into 'ter' and 'instruction'

-- First, backup the current instructions value (we'll use it as reference)
-- Add TER entries (default 60% of what instructions was)
INSERT INTO percentage_allocation (position_id, designation_id, category, sub_category, percentage)
SELECT position_id, designation_id, category, 'ter', percentage * 0.6
FROM percentage_allocation 
WHERE sub_category = 'instructions';

-- Add Instruction entries (default 40% of what instructions was)
INSERT INTO percentage_allocation (position_id, designation_id, category, sub_category, percentage)
SELECT position_id, designation_id, category, 'instruction', percentage * 0.4
FROM percentage_allocation 
WHERE sub_category = 'instructions';

-- Delete old instructions entries
DELETE FROM percentage_allocation WHERE sub_category = 'instructions';
