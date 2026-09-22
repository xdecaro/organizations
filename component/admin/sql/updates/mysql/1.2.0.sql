ALTER TABLE `#__xdecaroorganizations_appointments`
  ADD COLUMN `show_on_frontend` TINYINT(1) NOT NULL DEFAULT 0 AFTER `notes`;
