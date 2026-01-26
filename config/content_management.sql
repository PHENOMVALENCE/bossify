-- Content Management Tables
-- Run this after the main init.sql

USE bossify_academy;

-- Packages/Pricing Management Table
CREATE TABLE IF NOT EXISTS packages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    package_key VARCHAR(50) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(10) DEFAULT 'TZS',
    description TEXT,
    features TEXT, -- JSON array of features
    is_featured BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    display_order INT DEFAULT 0,
    button_text VARCHAR(100) DEFAULT 'Enroll Now',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_package_key (package_key),
    INDEX idx_is_active (is_active),
    INDEX idx_display_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default packages
INSERT INTO packages (package_key, title, price, currency, description, features, is_featured, display_order) VALUES
('single_track', 'Single Track Program', 150000, 'TZS', 'Individual course enrollment for focused learning', 
 '["Access to one specialized course track", "Comprehensive course materials and resources", "Exclusive WhatsApp community access"]', 
 FALSE, 1),
('full_cohort', 'Complete Cohort Program', 400000, 'TZS', 'Comprehensive program with all three courses and graduation ceremony', 
 '["All three signature course tracks", "Complete suite of course materials and resources", "Exclusive WhatsApp community membership", "Elegant graduation brunch ceremony", "Official certificate of completion"]', 
 TRUE, 2),
('corporate', 'Corporate Partnership', 0, 'TZS', 'Bespoke training solutions for organizational development', 
 '["Tailored curriculum design", "On-site or virtual delivery options", "Organizational team development focus", "Flexible scheduling accommodations", "Volume-based pricing considerations"]', 
 FALSE, 3)
ON DUPLICATE KEY UPDATE title=title;

-- Website Content Settings
CREATE TABLE IF NOT EXISTS site_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    setting_type VARCHAR(50) DEFAULT 'text',
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT,
    INDEX idx_setting_key (setting_key),
    FOREIGN KEY (updated_by) REFERENCES admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default settings
INSERT INTO site_settings (setting_key, setting_value, setting_type, description) VALUES
('site_name', 'Bossify Academy', 'text', 'Website name'),
('site_email', 'info@bossifyacademy.com', 'email', 'Main contact email'),
('site_phone', '+255 XXX XXX XXX', 'text', 'Contact phone number'),
('site_address', 'Dar es Salaam, Tanzania', 'text', 'Physical address'),
('hero_title', 'Bossify Academy: Empowering the Next Generation of Women Leaders', 'text', 'Hero section title'),
('hero_subtitle', 'Cultivate excellence in leadership, communication, and financial acumen within an empowering, Afrocentric learning environment.', 'textarea', 'Hero section subtitle'),
('pricing_section_title', 'Investment in Excellence', 'text', 'Pricing section title'),
('pricing_section_subtitle', 'Select the program option that best aligns with your professional development objectives', 'textarea', 'Pricing section subtitle')
ON DUPLICATE KEY UPDATE setting_key=setting_key;
