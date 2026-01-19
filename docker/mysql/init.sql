-- MySQL initialization script for GitPulse
-- This runs when the MySQL container is first created

-- Create testing database
CREATE DATABASE IF NOT EXISTS gitpulse_testing;
GRANT ALL PRIVILEGES ON gitpulse_testing.* TO 'gitpulse'@'%';

-- Optimize for development
SET GLOBAL max_connections = 200;
SET GLOBAL innodb_buffer_pool_size = 268435456; -- 256MB

FLUSH PRIVILEGES;
