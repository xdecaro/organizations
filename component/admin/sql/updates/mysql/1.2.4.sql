-- Organizations 1.2.4: split province and region without rewriting legacy geographic data.
ALTER TABLE `#__xdecaroorganizations_organizations`
    ADD COLUMN `province` VARCHAR(190) NULL AFTER `city`;
