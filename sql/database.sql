IF DB_ID('HMDS') IS NULL
BEGIN
    CREATE DATABASE HMDS;
END
GO

USE HMDS;
GO

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

SELECT * FROM dbo.users;
SELECT * FROM dbo.patients;
GO

USE HMDS;
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

-- enforce role values
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

SELECT id, name, email, role FROM dbo.users WHERE role='doctor';
SELECT * FROM dbo.doctors;