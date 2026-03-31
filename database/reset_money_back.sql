-- Reset Money Back System
-- Run this SQL to clear all money back data for testing

-- Delete money back recipient records
DELETE FROM money_back_recipients;

-- Delete distribution history
DELETE FROM money_back_distributions;

-- Delete income deductions
DELETE FROM company_income_deductions;

-- Optional: Delete money back savings transactions (uncomment if needed)
-- DELETE FROM savings_transactions WHERE category = 'Money Back';

-- Reset AUTO_INCREMENT counters
ALTER TABLE money_back_recipients AUTO_INCREMENT = 1;
ALTER TABLE money_back_distributions AUTO_INCREMENT = 1;
ALTER TABLE company_income_deductions AUTO_INCREMENT = 1;
