
-- Create database if not exists
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
GO

USE HMDS;
GO

-- Drop old table if exists (only for testing)
IF OBJECT_ID('dbo.patients', 'U') IS NOT NULL
DROP TABLE dbo.patients;
GO

-- Patients table
CREATE TABLE dbo.patients (
    id INT PRIMARY KEY,                     -- Same as users.id, no IDENTITY
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
GO

-- Check tables
SELECT * FROM dbo.users;
SELECT * FROM dbo.patients;
GO

