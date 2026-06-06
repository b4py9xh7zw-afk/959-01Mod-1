<?php
/**
 * Seed Users Script
 * This script creates initial users with correct password hashes
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Company.php';
require_once __DIR__ . '/../models/Channel.php';

try {
    $userModel = new User();
    $companyModel = new Company();
    $channelModel = new Channel();
    
    // Check if admin user exists
    $admin = $userModel->findByEmail('admin@license-platform.com');
    if (!$admin) {
        $userModel->create([
            'username' => 'admin',
            'email' => 'admin@license-platform.com',
            'password' => 'admin123',
            'role' => 'admin'
        ]);
        echo "Admin user created successfully.\n";
    } else {
        echo "Admin user already exists.\n";
    }
    
    // Check if test user exists
    $testUser = $userModel->findByEmail('user@license-platform.com');
    if (!$testUser) {
        $userModel->create([
            'username' => 'testuser',
            'email' => 'user@license-platform.com',
            'password' => 'user123',
            'role' => 'user'
        ]);
        echo "Test user created successfully.\n";
    } else {
        echo "Test user already exists.\n";
    }
    
    // Create sample channel partner
    $channel = $channelModel->findByName('科技渠道有限公司');
    if (!$channel) {
        $channelId = $channelModel->create([
            'name' => '科技渠道有限公司',
            'contact_person' => '张经理',
            'contact_email' => 'channel@example.com',
            'contact_phone' => '13800138001',
            'commission_rate' => 15.00,
            'status' => 'active'
        ]);
        echo "Sample channel created successfully.\n";
    } else {
        $channelId = $channel['id'];
        echo "Sample channel already exists.\n";
    }
    
    // Create sample enterprise company
    $company = $companyModel->findByName('创新科技有限公司');
    if (!$company) {
        $companyId = $companyModel->create([
            'name' => '创新科技有限公司',
            'contact_person' => '李总',
            'contact_email' => 'enterprise@example.com',
            'contact_phone' => '13900139001',
            'address' => '上海市浦东新区张江高科技园区',
            'channel_id' => $channelId,
            'status' => 'active'
        ]);
        echo "Sample company created successfully.\n";
    } else {
        $companyId = $company['id'];
        echo "Sample company already exists.\n";
    }
    
    // Check if channel partner user exists
    $channelUser = $userModel->findByEmail('channel@license-platform.com');
    if (!$channelUser) {
        $userModel->create([
            'username' => 'channel_partner',
            'email' => 'channel@license-platform.com',
            'password' => 'channel123',
            'role' => 'channel_partner',
            'channel_id' => $channelId,
            'contact_name' => '张经理',
            'phone' => '13800138001'
        ]);
        echo "Channel partner user created successfully.\n";
    } else {
        echo "Channel partner user already exists.\n";
    }
    
    // Check if enterprise user exists
    $enterpriseUser = $userModel->findByEmail('enterprise@license-platform.com');
    if (!$enterpriseUser) {
        $userModel->create([
            'username' => 'enterprise',
            'email' => 'enterprise@license-platform.com',
            'password' => 'enterprise123',
            'role' => 'enterprise',
            'company_id' => $companyId,
            'channel_id' => $channelId,
            'contact_name' => '李总',
            'phone' => '13900139001'
        ]);
        echo "Enterprise user created successfully.\n";
    } else {
        echo "Enterprise user already exists.\n";
    }
    
    echo "User seeding completed!\n";
} catch (Exception $e) {
    error_log("User seeding failed: " . $e->getMessage());
    echo "User seeding failed: " . $e->getMessage() . "\n";
    exit(1);
}
