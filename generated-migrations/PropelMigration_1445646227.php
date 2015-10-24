<?php

/**
 * Data object containing the SQL and PHP code to migrate the database
 * up to version 1445646227.
 * Generated on 2015-10-24 00:23:47 by vagrant
 */
class PropelMigration_1445646227
{
    public $comment = '';

    public function preUp($manager)
    {
        // add the pre-migration code here
    }

    public function postUp($manager)
    {
        // add the post-migration code here
    }

    public function preDown($manager)
    {
        // add the pre-migration code here
    }

    public function postDown($manager)
    {
        // add the post-migration code here
    }

    /**
     * Get the SQL statements for the Up migration
     *
     * @return array list of the SQL strings to execute for the Up migration
     *               the keys being the datasources
     */
    public function getUpSQL()
    {
        return array (
  'default' => '
# This is a fix for InnoDB in MySQL >= 4.1.x
# It "suspends judgement" for fkey relationships until are tables are set.
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE `user`
(
    `id` CHAR(64) NOT NULL,
    `private_key` CHAR(64) NOT NULL,
    `name` VARCHAR(255),
    `image_id` INTEGER,
    PRIMARY KEY (`id`),
    INDEX `user_fi_810cd0` (`image_id`),
    CONSTRAINT `user_fk_810cd0`
        FOREIGN KEY (`image_id`)
        REFERENCES `image` (`id`)
) ENGINE=InnoDB;

CREATE TABLE `question`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `body` TEXT NOT NULL,
    `created` TIMESTAMP NOT NULL DEFAULT now(),
    `author_id` VARCHAR(64) NOT NULL,
    `total_votes` INTEGER DEFAULT 0 NOT NULL,
    PRIMARY KEY (`id`),
    INDEX `question_fi_0319d7` (`author_id`),
    CONSTRAINT `question_fk_0319d7`
        FOREIGN KEY (`author_id`)
        REFERENCES `user` (`id`)
) ENGINE=InnoDB;

CREATE TABLE `answer`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `question_id` INTEGER NOT NULL,
    `image_id` INTEGER NOT NULL,
    `vote_count` INTEGER DEFAULT 0 NOT NULL,
    `complaint_count` INTEGER DEFAULT 0 NOT NULL,
    `weight` TINYINT NOT NULL,
    PRIMARY KEY (`id`),
    INDEX `answer_fi_3ff0cc` (`question_id`),
    INDEX `answer_fi_810cd0` (`image_id`),
    CONSTRAINT `answer_fk_3ff0cc`
        FOREIGN KEY (`question_id`)
        REFERENCES `question` (`id`),
    CONSTRAINT `answer_fk_810cd0`
        FOREIGN KEY (`image_id`)
        REFERENCES `image` (`id`)
) ENGINE=InnoDB;

CREATE TABLE `image`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `filename` CHAR,
    `author_id` CHAR NOT NULL,
    `type` TINYINT,
    PRIMARY KEY (`id`),
    INDEX `image_fi_0319d7` (`author_id`),
    CONSTRAINT `image_fk_0319d7`
        FOREIGN KEY (`author_id`)
        REFERENCES `user` (`id`)
) ENGINE=InnoDB;

CREATE TABLE `vote`
(
    `created` TIMESTAMP NOT NULL DEFAULT now(),
    `user_id` CHAR NOT NULL,
    `answer_id` INTEGER NOT NULL,
    INDEX `vote_fi_29554a` (`user_id`),
    INDEX `vote_fi_f8b5b4` (`answer_id`),
    CONSTRAINT `vote_fk_29554a`
        FOREIGN KEY (`user_id`)
        REFERENCES `user` (`id`),
    CONSTRAINT `vote_fk_f8b5b4`
        FOREIGN KEY (`answer_id`)
        REFERENCES `answer` (`id`)
) ENGINE=InnoDB;

# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;
',
);
    }

    /**
     * Get the SQL statements for the Down migration
     *
     * @return array list of the SQL strings to execute for the Down migration
     *               the keys being the datasources
     */
    public function getDownSQL()
    {
        return array (
  'default' => '
# This is a fix for InnoDB in MySQL >= 4.1.x
# It "suspends judgement" for fkey relationships until are tables are set.
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `user`;

DROP TABLE IF EXISTS `question`;

DROP TABLE IF EXISTS `answer`;

DROP TABLE IF EXISTS `image`;

DROP TABLE IF EXISTS `vote`;

# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;
',
);
    }

}