IF DB_ID('HMDS') IS NULL
BEGIN
    CREATE DATABASE HMDS;
END
GO

USE HMDS;
GO

-- Users table
IF OBJECT_ID('dbo.users', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.users (
        id INT IDENTITY(1,1) PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(120) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        role VARCHAR(20) NOT NULL DEFAULT 'patient',
        created_at DATETIME DEFAULT GETDATE()
    );
END
ELSE
BEGIN
    IF COL_LENGTH('dbo.users', 'role') IS NULL
    BEGIN
        ALTER TABLE dbo.users
        ADD role VARCHAR(20) NOT NULL 
        CONSTRAINT DF_users_role DEFAULT 'patient';
    END
END
GO

-- Patients table
IF OBJECT_ID('dbo.patients', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.patients (
        id INT PRIMARY KEY,
        PatientName VARCHAR(100) NULL,
        DateOfBirth DATE NULL,
        Gender VARCHAR(20) NULL,
        Phone VARCHAR(20) NULL,
        Address VARCHAR(255) NULL,
        MaritalStatus VARCHAR(50) NULL,
        created_at DATETIME DEFAULT GETDATE(),
        updated_at DATETIME DEFAULT GETDATE(),
        CONSTRAINT FK_Patients_Users FOREIGN KEY (id)
            REFERENCES dbo.users(id)
            ON DELETE CASCADE
    );
END
GO

-- Doctors table
IF OBJECT_ID('dbo.doctors', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.doctors (
        id INT PRIMARY KEY,              -- same as users.id
        full_name VARCHAR(100) NULL,
        department VARCHAR(100) NULL,
        phone VARCHAR(20) NULL,
        room_no VARCHAR(20) NULL,
        created_at DATETIME DEFAULT GETDATE(),
        updated_at DATETIME DEFAULT GETDATE(),
        CONSTRAINT FK_Doctors_Users FOREIGN KEY (id)
            REFERENCES dbo.users(id)
            ON DELETE CASCADE
    );
END
GO

-- Appointments table
IF OBJECT_ID('dbo.appointments', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.appointments (
        id INT IDENTITY(1,1) PRIMARY KEY,
        patient_id INT NOT NULL,
        doctor_id INT NOT NULL,
        appointment_date DATE NOT NULL,
        appointment_time TIME NOT NULL,
        serial_no INT NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'Pending',
        created_at DATETIME DEFAULT GETDATE(),
        updated_at DATETIME DEFAULT GETDATE(),

        CONSTRAINT FK_Appointments_Patients FOREIGN KEY (patient_id)
            REFERENCES dbo.patients(id)
            ON DELETE CASCADE,

        CONSTRAINT FK_Appointments_Doctors FOREIGN KEY (doctor_id)
            REFERENCES dbo.doctors(id)
            ON DELETE CASCADE
    );
END
GO

-- Vaccines table
IF OBJECT_ID('dbo.vaccines', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.vaccines (
        vaccine_id INT IDENTITY(1,1) PRIMARY KEY,
        vaccine_name VARCHAR(150) NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        min_age INT NOT NULL,
        max_age INT NOT NULL,
        gender_applicable VARCHAR(20) NOT NULL DEFAULT 'Both',
        preparation_notes VARCHAR(500) NULL,
        created_at DATETIME DEFAULT GETDATE(),
        updated_at DATETIME DEFAULT GETDATE()
    );
END
GO

IF NOT EXISTS (SELECT 1 FROM sys.check_constraints WHERE name = 'CK_vaccines_age_range')
BEGIN
    ALTER TABLE dbo.vaccines
    ADD CONSTRAINT CK_vaccines_age_range CHECK (min_age >= 0 AND max_age >= min_age);
END
GO

IF NOT EXISTS (SELECT 1 FROM sys.check_constraints WHERE name = 'CK_vaccines_gender')
BEGIN
    ALTER TABLE dbo.vaccines
    ADD CONSTRAINT CK_vaccines_gender CHECK (gender_applicable IN ('Both','Male','Female','Other'));
END
GO

-- Vaccine billing/download history
IF OBJECT_ID('dbo.vaccine_billing_history', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.vaccine_billing_history (
        billing_id INT IDENTITY(1,1) PRIMARY KEY,
        vaccine_id INT NOT NULL,
        user_id INT NULL,
        patient_name VARCHAR(100) NOT NULL,
        patient_age_value INT NOT NULL,
        patient_age_unit VARCHAR(20) NOT NULL,
        patient_gender VARCHAR(20) NOT NULL,
        patient_phone VARCHAR(30) NOT NULL,
        patient_address VARCHAR(255) NOT NULL,
        billed_price DECIMAL(10,2) NOT NULL,
        downloaded_at DATETIME NOT NULL DEFAULT GETDATE(),
        CONSTRAINT FK_VaccineBillingHistory_Vaccines FOREIGN KEY (vaccine_id)
            REFERENCES dbo.vaccines(vaccine_id)
            ON DELETE CASCADE,
        CONSTRAINT FK_VaccineBillingHistory_Users FOREIGN KEY (user_id)
            REFERENCES dbo.users(id)
            ON DELETE SET NULL
    );
END
GO

IF NOT EXISTS (SELECT 1 FROM sys.check_constraints WHERE name = 'CK_vaccine_billing_history_age_unit')
BEGIN
    ALTER TABLE dbo.vaccine_billing_history
    ADD CONSTRAINT CK_vaccine_billing_history_age_unit CHECK (patient_age_unit IN ('months','years'));
END
GO

-- Enforce role values
IF NOT EXISTS (SELECT 1 FROM sys.check_constraints WHERE name = 'CK_users_role')
BEGIN
  ALTER TABLE dbo.users
  ADD CONSTRAINT CK_users_role CHECK (role IN ('patient','doctor','admin'));
END
GO

-- Creating admin user (ONLY ONCE)
IF NOT EXISTS (SELECT 1 FROM dbo.users WHERE email = 'admin@hmds.com')
BEGIN
    INSERT INTO dbo.users (name, email, password_hash, role)
    VALUES ('Admin', 'admin@hmds.com', '$2y$10$uV80.u6SOMkt74wKLHb1cu3VE/NLCke3ibcFoySqbOI29NnDqhjOe', 'admin');
END
GO

-- Optional: show current doctors and appointments
SELECT id, name, email, role FROM dbo.users WHERE role='doctor';
SELECT * FROM dbo.doctors;
SELECT * FROM dbo.patients;
SELECT * FROM dbo.appointments;
SELECT * FROM dbo.vaccines;
SELECT * FROM dbo.vaccine_billing_history;
GO

CREATE TABLE doctor_schedule (
    id INT IDENTITY(1,1) PRIMARY KEY,
    doctor_id INT NOT NULL,
    schedule_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    max_patients INT NOT NULL,
    created_at DATETIME DEFAULT GETDATE()
);

ALTER TABLE appointments
ADD schedule_id INT;

-- 1. Add day_of_week column
ALTER TABLE doctor_schedule
ADD day_of_week INT;

-- 2. Remove schedule_date (not needed anymore)
ALTER TABLE doctor_schedule
DROP COLUMN schedule_date;

IF OBJECT_ID('dbo.departments', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.departments (
        department_id INT IDENTITY(1,1) PRIMARY KEY,
        department_name VARCHAR(100) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT GETDATE()
    );
END
GO

IF NOT EXISTS (
    SELECT 1
    FROM sys.key_constraints
    WHERE name = 'UQ_departments_department_name'
)
BEGIN
    ALTER TABLE dbo.departments
    ADD CONSTRAINT UQ_departments_department_name UNIQUE (department_name);
END
GO

INSERT INTO dbo.departments (department_name)
SELECT v.department_name
FROM (VALUES
    ('Cardiology'),
    ('Neurology'),
    ('Orthopedics'),
    ('Pediatrics'),
    ('Dermatology'),
    ('Gynecology'),
    ('ENT'),
    ('Surgery'),
    ('Medicine'),
    ('Oncology'),
    ('Urology'),
    ('Psychiatry'),
    ('General Medicine'),
    ('Radiology'),
    ('Anesthesiology'),
    ('Emergency')
) AS v(department_name)
WHERE NOT EXISTS (
    SELECT 1
    FROM dbo.departments d
    WHERE d.department_name = v.department_name
);
GO

IF COL_LENGTH('dbo.doctors', 'department_id') IS NULL
BEGIN
    ALTER TABLE dbo.doctors
    ADD department_id INT NULL;
END
GO

IF COL_LENGTH('dbo.doctors', 'department') IS NOT NULL
BEGIN
    INSERT INTO dbo.departments (department_name)
    SELECT DISTINCT LTRIM(RTRIM(d.department))
    FROM dbo.doctors d
    WHERE d.department IS NOT NULL
      AND LTRIM(RTRIM(d.department)) <> ''
      AND NOT EXISTS (
          SELECT 1
          FROM dbo.departments dep
          WHERE dep.department_name = LTRIM(RTRIM(d.department))
      );

    UPDATE d
    SET d.department_id = dep.department_id
    FROM dbo.doctors d
    INNER JOIN dbo.departments dep
        ON dep.department_name = LTRIM(RTRIM(d.department))
    WHERE d.department_id IS NULL
      AND d.department IS NOT NULL
      AND LTRIM(RTRIM(d.department)) <> '';
END
GO

IF NOT EXISTS (
    SELECT 1
    FROM sys.foreign_keys
    WHERE name = 'FK_Doctors_Departments'
)
BEGIN
    ALTER TABLE dbo.doctors
    ADD CONSTRAINT FK_Doctors_Departments
        FOREIGN KEY (department_id)
        REFERENCES dbo.departments(department_id);
END
GO

IF NOT EXISTS (
    SELECT 1
    FROM sys.indexes
    WHERE name = 'IX_doctors_department_id'
      AND object_id = OBJECT_ID('dbo.doctors')
)
BEGIN
    CREATE INDEX IX_doctors_department_id
    ON dbo.doctors(department_id);
END
GO

SELECT department_id, department_name
FROM dbo.departments
ORDER BY department_name;

SELECT d.id, d.full_name, d.department_id, dep.department_name
FROM dbo.doctors d
LEFT JOIN dbo.departments dep ON dep.department_id = d.department_id
ORDER BY d.id;

SELECT *
FROM dbo.departments
WHERE department_name IN ('Neurology', 'Neurologist');

DELETE FROM dbo.departments
WHERE department_name = 'Neurologist';
GO

SELECT department_id, department_name
FROM dbo.departments
ORDER BY department_name;

IF COL_LENGTH('dbo.doctors', 'department') IS NOT NULL
BEGIN
    ALTER TABLE dbo.doctors
    DROP COLUMN department;
END
GO