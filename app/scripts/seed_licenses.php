<?php
/**
 * Seed Licenses Script
 * This script creates sample licenses after users are created
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/License.php';
require_once __DIR__ . '/../models/Company.php';
require_once __DIR__ . '/../models/Channel.php';

try {
    $userModel = new User();
    $licenseModel = new License();
    $companyModel = new Company();
    $channelModel = new Channel();
    
    // Get admin user
    $admin = $userModel->findByEmail('admin@license-platform.com');
    if (!$admin) {
        echo "Admin user not found. Please run seed_users.php first.\n";
        exit(1);
    }
    
    // Get test user
    $testUser = $userModel->findByEmail('user@license-platform.com');
    if (!$testUser) {
        echo "Test user not found. Please run seed_users.php first.\n";
        exit(1);
    }
    
    // Get enterprise user
    $enterpriseUser = $userModel->findByEmail('enterprise@license-platform.com');
    if (!$enterpriseUser) {
        echo "Enterprise user not found. Please run seed_users.php first.\n";
        exit(1);
    }
    
    // Get sample company and channel
    $company = $companyModel->findByName('创新科技有限公司');
    $channel = $channelModel->findByName('科技渠道有限公司');
    
    // Check if licenses already exist
    $existingLicenses = $licenseModel->findAll(10, 0);
    if (count($existingLicenses) > 0) {
        echo "Licenses already exist. Skipping seed.\n";
        exit(0);
    }
    
    // Create sample licenses for admin
    $licenseModel->create([
        'user_id' => $admin['id'],
        'product_name' => 'Premium Software License',
        'status' => 'active',
        'expires_at' => date('Y-m-d H:i:s', strtotime('+1 year')),
        'seats' => 50,
        'grace_period_days' => 30,
        'invoice_required' => 1,
        'renewal_status' => 'active'
    ]);
    
    $licenseModel->create([
        'user_id' => $admin['id'],
        'product_name' => 'Enterprise License',
        'status' => 'active',
        'expires_at' => date('Y-m-d H:i:s', strtotime('+2 years')),
        'seats' => 100,
        'grace_period_days' => 60,
        'invoice_required' => 1,
        'renewal_status' => 'active'
    ]);
    
    // Create sample licenses for test user
    $licenseModel->create([
        'user_id' => $testUser['id'],
        'product_name' => 'Basic License',
        'status' => 'active',
        'expires_at' => date('Y-m-d H:i:s', strtotime('+6 months')),
        'seats' => 5,
        'grace_period_days' => 15,
        'invoice_required' => 0,
        'renewal_status' => 'active'
    ]);
    
    $licenseModel->create([
        'user_id' => $testUser['id'],
        'product_name' => 'Trial License',
        'status' => 'expired',
        'expires_at' => date('Y-m-d H:i:s', strtotime('-1 month')),
        'seats' => 1,
        'grace_period_days' => 7,
        'grace_period_end' => date('Y-m-d H:i:s', strtotime('-1 week')),
        'invoice_required' => 0,
        'renewal_status' => 'expired'
    ]);
    
    // Create sample enterprise license with company and channel association
    if ($company && $channel) {
        $licenseModel->create([
            'user_id' => $enterpriseUser['id'],
            'product_name' => 'Enterprise Solution License',
            'status' => 'active',
            'expires_at' => date('Y-m-d H:i:s', strtotime('+3 months')),
            'company_id' => $company['id'],
            'channel_id' => $channel['id'],
            'seats' => 200,
            'grace_period_days' => 45,
            'invoice_required' => 1,
            'renewal_status' => 'pending_renewal'
        ]);
        
        // Create another license in grace period
        $licenseModel->create([
            'user_id' => $enterpriseUser['id'],
            'product_name' => 'Legacy System License',
            'status' => 'grace_period',
            'expires_at' => date('Y-m-d H:i:s', strtotime('-10 days')),
            'company_id' => $company['id'],
            'channel_id' => $channel['id'],
            'seats' => 50,
            'grace_period_days' => 30,
            'grace_period_end' => date('Y-m-d H:i:s', strtotime('+20 days')),
            'invoice_required' => 1,
            'renewal_status' => 'in_renewal'
        ]);
        
        // Create a frozen license
        $licenseModel->create([
            'user_id' => $enterpriseUser['id'],
            'product_name' => 'Frozen License',
            'status' => 'active',
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 year')),
            'company_id' => $company['id'],
            'channel_id' => $channel['id'],
            'seats' => 25,
            'grace_period_days' => 30,
            'is_frozen' => 1,
            'invoice_required' => 1,
            'renewal_status' => 'active'
        ]);
    }
    
    echo "Sample licenses created successfully!\n";
} catch (Exception $e) {
    error_log("License seeding failed: " . $e->getMessage());
    echo "License seeding failed: " . $e->getMessage() . "\n";
    exit(1);
}
