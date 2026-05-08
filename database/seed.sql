USE quanlyktx;

INSERT INTO users (username, password_hash, full_name, email, phone, role, status)
VALUES (
    'admin',
    '$2y$10$YDlEZKzUZNpDzsfgUSdaOO5.AF0T1Dz7uCRcOwyziLuNuPLU9Tnya',
    'Quản trị hệ thống',
    'admin@quanlyktx.local',
    NULL,
    1,
    1
);
