-- Rename existing tables to the sales_ prefix
USE salescase;

RENAME TABLE cases TO sales_cases;
RENAME TABLE completed_cases TO sales_completed_cases;
RENAME TABLE case_attachments TO sales_case_attachments;
RENAME TABLE history TO sales_history;
RENAME TABLE case_sequence TO sales_case_sequence;
RENAME TABLE branches TO sales_branches;
RENAME TABLE technicians TO sales_technicians;
RENAME TABLE users TO sales_users;
RENAME TABLE settings TO sales_settings;
RENAME TABLE comments TO sales_comments;
RENAME TABLE telegram_destinations TO sales_telegram_destinations;
