-- License Platform Migration: Renewal and Deactivation Features
-- Adds support for enterprise renewal management, grace periods,
-- invoice confirmation, seat freezing, device unbinding, channel settlements,
-- and channel partner permissions.

-- Add channel_partner role to users
ALTER TABLE users 
MODIFY COLUMN role ENUM('user', 'admin', 'channel_partner', 'enterprise') DEFAULT 'user';

-- Add channel and company fields to users
ALTER TABLE users 
ADD COLUMN company_id INT NULL,
ADD COLUMN channel_id INT NULL,
ADD COLUMN contact_person VARCHAR(100) NULL,
ADD COLUMN contact_phone VARCHAR(20) NULL,
ADD COLUMN address TEXT NULL,
ADD INDEX idx_company_id (company_id),
ADD INDEX idx_channel_id (channel_id);

-- Update licenses table for renewal features
ALTER TABLE licenses 
ADD COLUMN company_id INT NULL,
ADD COLUMN channel_id INT NULL,
ADD COLUMN seats INT DEFAULT 1,
ADD COLUMN grace_period_days INT DEFAULT 30,
ADD COLUMN grace_period_end DATETIME NULL,
ADD COLUMN is_frozen TINYINT(1) DEFAULT 0,
ADD COLUMN invoice_required TINYINT(1) DEFAULT 0,
ADD COLUMN renewal_status ENUM('active', 'pending_renewal', 'expired', 'in_grace_period', 'suspended') DEFAULT 'active',
ADD COLUMN last_renewed_at DATETIME NULL,
ADD COLUMN auto_renew TINYINT(1) DEFAULT 0,
ADD COLUMN activation_code_generated TINYINT(1) DEFAULT 0,
ADD COLUMN original_license_id INT NULL,
ADD INDEX idx_company_id (company_id),
ADD INDEX idx_channel_id (channel_id),
ADD INDEX idx_renewal_status (renewal_status),
ADD INDEX idx_grace_period_end (grace_period_end),
ADD INDEX idx_is_frozen (is_frozen);

-- Companies table (enterprise customers)
CREATE TABLE IF NOT EXISTS companies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    business_license VARCHAR(100) NULL,
    contact_person VARCHAR(100) NULL,
    contact_phone VARCHAR(20) NULL,
    contact_email VARCHAR(255) NULL,
    address TEXT NULL,
    channel_id INT NULL,
    status ENUM('active', 'inactive', 'expired') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_channel_id (channel_id),
    INDEX idx_status (status),
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Channels table (channel partners)
CREATE TABLE IF NOT EXISTS channels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    contact_person VARCHAR(100) NULL,
    contact_phone VARCHAR(20) NULL,
    contact_email VARCHAR(255) NULL,
    commission_rate DECIMAL(5,2) DEFAULT 0.10,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Devices table (device binding records)
CREATE TABLE IF NOT EXISTS devices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    license_id INT NOT NULL,
    user_id INT NOT NULL,
    company_id INT NULL,
    device_uuid VARCHAR(100) NOT NULL,
    device_name VARCHAR(255) NULL,
    device_type VARCHAR(50) NULL,
    os_version VARCHAR(100) NULL,
    hardware_info TEXT NULL,
    ip_address VARCHAR(45) NULL,
    mac_address VARCHAR(20) NULL,
    last_activated_at DATETIME NULL,
    is_bound TINYINT(1) DEFAULT 1,
    bound_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    unbound_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_license_id (license_id),
    INDEX idx_user_id (user_id),
    INDEX idx_company_id (company_id),
    INDEX idx_device_uuid (device_uuid),
    INDEX idx_is_bound (is_bound),
    FOREIGN KEY (license_id) REFERENCES licenses(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Invoices table
CREATE TABLE IF NOT EXISTS invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_no VARCHAR(50) NOT NULL UNIQUE,
    license_id INT NOT NULL,
    company_id INT NULL,
    user_id INT NULL,
    channel_id INT NULL,
    amount DECIMAL(10,2) NOT NULL,
    tax_amount DECIMAL(10,2) DEFAULT 0,
    invoice_type ENUM('vat_special', 'vat_general', 'general') DEFAULT 'general',
    invoice_title VARCHAR(255) NOT NULL,
    taxpayer_id VARCHAR(50) NULL,
    address TEXT NULL,
    phone VARCHAR(20) NULL,
    bank_name VARCHAR(255) NULL,
    bank_account VARCHAR(50) NULL,
    status ENUM('pending', 'confirmed', 'rejected', 'cancelled') DEFAULT 'pending',
    issued_at DATETIME NULL,
    confirmed_at DATETIME NULL,
    confirmed_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_license_id (license_id),
    INDEX idx_company_id (company_id),
    INDEX idx_channel_id (channel_id),
    INDEX idx_status (status),
    INDEX idx_invoice_no (invoice_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Contracts table
CREATE TABLE IF NOT EXISTS contracts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contract_no VARCHAR(50) NOT NULL UNIQUE,
    license_id INT NOT NULL,
    company_id INT NULL,
    channel_id INT NULL,
    contract_type ENUM('new', 'renewal', 'upgrade', 'downgrade') DEFAULT 'new',
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    seats INT DEFAULT 1,
    status ENUM('draft', 'active', 'expired', 'terminated') DEFAULT 'draft',
    signed_at DATETIME NULL,
    signed_by_company VARCHAR(255) NULL,
    signed_by_platform VARCHAR(255) NULL,
    contract_file VARCHAR(255) NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_license_id (license_id),
    INDEX idx_company_id (company_id),
    INDEX idx_channel_id (channel_id),
    INDEX idx_status (status),
    INDEX idx_contract_no (contract_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Operation logs table
CREATE TABLE IF NOT EXISTS operation_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    company_id INT NULL,
    license_id INT NULL,
    action VARCHAR(50) NOT NULL,
    action_type ENUM('create', 'update', 'delete', 'renewal', 'suspension', 'activation', 'unbinding', 'invoice', 'login', 'other', 'freeze', 'unfreeze', 'generate_code', 'settlement') DEFAULT 'other',
    description TEXT NULL,
    old_value TEXT NULL,
    new_value TEXT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_company_id (company_id),
    INDEX idx_license_id (license_id),
    INDEX idx_action (action),
    INDEX idx_action_type (action_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Channel settlements table
CREATE TABLE IF NOT EXISTS channel_settlements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    settlement_no VARCHAR(50) NOT NULL UNIQUE,
    channel_id INT NOT NULL,
    license_id INT NOT NULL,
    invoice_id INT NULL,
    company_id INT NULL,
    transaction_amount DECIMAL(10,2) NOT NULL,
    commission_rate DECIMAL(5,2) DEFAULT 0.10,
    commission_amount DECIMAL(10,2) NOT NULL,
    settlement_date DATE NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'cancelled') DEFAULT 'pending',
    paid_at DATETIME NULL,
    paid_by INT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_channel_id (channel_id),
    INDEX idx_license_id (license_id),
    INDEX idx_company_id (company_id),
    INDEX idx_status (status),
    INDEX idx_settlement_date (settlement_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Renewal pools table (for channel partners to manage their customers' renewals)
CREATE TABLE IF NOT EXISTS renewal_pools (
    id INT AUTO_INCREMENT PRIMARY KEY,
    license_id INT NOT NULL,
    company_id INT NOT NULL,
    channel_id INT NOT NULL,
    original_expires_at DATETIME NOT NULL,
    renewal_deadline DATETIME NOT NULL,
    renewal_status ENUM('pending', 'in_progress', 'completed', 'expired') DEFAULT 'pending',
    renewal_amount DECIMAL(10,2) NULL,
    renewal_months INT DEFAULT 12,
    notified_count INT DEFAULT 0,
    last_notified_at DATETIME NULL,
    assigned_to INT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_license_id (license_id),
    INDEX idx_company_id (company_id),
    INDEX idx_channel_id (channel_id),
    INDEX idx_renewal_status (renewal_status),
    INDEX idx_renewal_deadline (renewal_deadline)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Activation codes table (for enterprise license activation)
CREATE TABLE IF NOT EXISTS activation_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    license_id INT NOT NULL,
    company_id INT NULL,
    generated_by INT NOT NULL,
    used_by INT NULL,
    used_at DATETIME NULL,
    expires_at DATETIME NULL,
    max_activations INT DEFAULT 1,
    used_count INT DEFAULT 0,
    status ENUM('active', 'used', 'expired', 'revoked') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_license_id (license_id),
    INDEX idx_company_id (company_id),
    INDEX idx_code (code),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
