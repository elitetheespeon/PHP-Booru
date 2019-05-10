/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `alias`
--

DROP TABLE IF EXISTS `alias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `alias` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `alias` text CHARACTER SET utf8 NOT NULL,
  `tag` text CHARACTER SET utf8 NOT NULL,
  `reason` text CHARACTER SET utf8 NOT NULL,
  `status` varchar(25) NOT NULL,
  `creator_id` varchar(45) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `alias`
--

LOCK TABLES `alias` WRITE;
/*!40000 ALTER TABLE `alias` DISABLE KEYS */;
/*!40000 ALTER TABLE `alias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `avatars`
--

DROP TABLE IF EXISTS `avatars`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `avatars` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(32) NOT NULL,
  `file_ext` varchar(45) NOT NULL,
  `height` int(32) NOT NULL DEFAULT '0',
  `width` int(32) NOT NULL DEFAULT '0',
  `md5` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `avatars`
--

LOCK TABLES `avatars` WRITE;
/*!40000 ALTER TABLE `avatars` DISABLE KEYS */;
/*!40000 ALTER TABLE `avatars` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `banned_ip`
--

DROP TABLE IF EXISTS `banned_ip`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `banned_ip` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(255) DEFAULT NULL,
  `user` text,
  `reason` text,
  `date_added` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ip` (`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `banned_ip`
--

LOCK TABLES `banned_ip` WRITE;
/*!40000 ALTER TABLE `banned_ip` DISABLE KEYS */;
/*!40000 ALTER TABLE `banned_ip` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `comment_votes`
--

DROP TABLE IF EXISTS `comment_votes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `comment_votes` (
  `ip` varchar(15) DEFAULT NULL,
  `post_id` bigint(20) unsigned DEFAULT NULL,
  `comment_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comment_votes`
--

LOCK TABLES `comment_votes` WRITE;
/*!40000 ALTER TABLE `comment_votes` DISABLE KEYS */;
/*!40000 ALTER TABLE `comment_votes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `comments`
--

DROP TABLE IF EXISTS `comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `comments` (
  `id` int(32) NOT NULL AUTO_INCREMENT,
  `posted_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `post_id` int(32) NOT NULL,
  `user` int(32) DEFAULT NULL,
  `comment` text NOT NULL,
  `ip` varchar(15) NOT NULL,
  `spam` tinyint(1) DEFAULT NULL,
  `text_search_index` longtext,
  `score` int(32) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_comments__post` (`post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comments`
--

LOCK TABLES `comments` WRITE;
/*!40000 ALTER TABLE `comments` DISABLE KEYS */;
/*!40000 ALTER TABLE `comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dmails`
--

DROP TABLE IF EXISTS `dmails`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dmails` (
  `id` int(32) NOT NULL AUTO_INCREMENT,
  `from_id` int(32) NOT NULL,
  `to_id` int(32) NOT NULL,
  `title` text NOT NULL,
  `body` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `has_seen` tinyint(1) NOT NULL DEFAULT '0',
  `parent_id` int(32) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `index_dmails_on_from_id` (`from_id`),
  KEY `index_dmails_on_parent_id` (`parent_id`),
  KEY `index_dmails_on_to_id` (`to_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dmails`
--

LOCK TABLES `dmails` WRITE;
/*!40000 ALTER TABLE `dmails` DISABLE KEYS */;
/*!40000 ALTER TABLE `dmails` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `favorites`
--

DROP TABLE IF EXISTS `favorites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `favorites` (
  `id` int(32) NOT NULL AUTO_INCREMENT,
  `post_id` int(32) NOT NULL,
  `user_id` int(32) NOT NULL,
  `added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_favorites__post` (`post_id`),
  KEY `idx_favorites__user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `favorites`
--

LOCK TABLES `favorites` WRITE;
/*!40000 ALTER TABLE `favorites` DISABLE KEYS */;
/*!40000 ALTER TABLE `favorites` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `favorites_count`
--

DROP TABLE IF EXISTS `favorites_count`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `favorites_count` (
  `user_id` bigint(20) unsigned NOT NULL,
  `fcount` bigint(20) unsigned DEFAULT '0',
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `favorites_count`
--

LOCK TABLES `favorites_count` WRITE;
/*!40000 ALTER TABLE `favorites_count` DISABLE KEYS */;
/*!40000 ALTER TABLE `favorites_count` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `flagged_post`
--

DROP TABLE IF EXISTS `flagged_post`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `flagged_post` (
  `id` int(32) NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `post_id` int(32) NOT NULL,
  `reason` text NOT NULL,
  `user_id` int(32) NOT NULL,
  `reported_score` int(32) NOT NULL,
  `is_resolved` tinyint(1) NOT NULL,
  `staff_id` int(32) DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `staff_comment` text,
  `dmail_sent` tinyint(1) DEFAULT NULL,
  `staff_action` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `index_flagged_post_details_on_post_id` (`post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `flagged_post`
--

LOCK TABLES `flagged_post` WRITE;
/*!40000 ALTER TABLE `flagged_post` DISABLE KEYS */;
/*!40000 ALTER TABLE `flagged_post` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `forum_posts`
--

DROP TABLE IF EXISTS `forum_posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `forum_posts` (
  `id` bigint(99) unsigned NOT NULL AUTO_INCREMENT,
  `title` text,
  `post` text NOT NULL,
  `author` varchar(256) DEFAULT NULL,
  `creation_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `topic_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  FULLTEXT KEY `post` (`post`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `forum_posts`
--

LOCK TABLES `forum_posts` WRITE;
/*!40000 ALTER TABLE `forum_posts` DISABLE KEYS */;
/*!40000 ALTER TABLE `forum_posts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `forum_topics`
--

DROP TABLE IF EXISTS `forum_topics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `forum_topics` (
  `id` bigint(99) unsigned NOT NULL AUTO_INCREMENT,
  `topic` text,
  `author` varchar(256) DEFAULT NULL,
  `last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `creation_post` bigint(20) unsigned NOT NULL,
  `priority` int(99) unsigned DEFAULT '0',
  `locked` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `forum_topics`
--

LOCK TABLES `forum_topics` WRITE;
/*!40000 ALTER TABLE `forum_topics` DISABLE KEYS */;
/*!40000 ALTER TABLE `forum_topics` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `groups`
--

DROP TABLE IF EXISTS `groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `groups` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `group_name` text,
  `delete_posts` tinyint(1) DEFAULT '0',
  `delete_comments` tinyint(1) DEFAULT '0',
  `admin_panel` tinyint(1) DEFAULT '0',
  `reverse_notes` tinyint(1) DEFAULT '0',
  `reverse_tags` tinyint(1) DEFAULT '0',
  `default_group` tinyint(1) DEFAULT '1',
  `is_admin` tinyint(1) DEFAULT '0',
  `delete_forum_posts` tinyint(1) DEFAULT '0',
  `delete_forum_topics` tinyint(1) DEFAULT '0',
  `lock_forum_topics` tinyint(1) DEFAULT '0',
  `edit_forum_posts` tinyint(1) DEFAULT '0',
  `pin_forum_topics` tinyint(1) DEFAULT '0',
  `alter_notes` tinyint(1) DEFAULT '0',
  `can_upload` tinyint(1) DEFAULT '1',
  `approve_posts` tinyint(1) DEFAULT '0',
  `edit_posts` tinyint(1) DEFAULT '1',
  `delete_wiki` tinyint(1) DEFAULT '0',
  `edit_wiki` tinyint(1) DEFAULT '1',
  `change_wiki_title` tinyint(1) DEFAULT '0',
  `reverse_wiki` tinyint(1) DEFAULT '0',
  `lock_wiki` tinyint(1) DEFAULT '0',
  `rename_wiki` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `groups`
--

LOCK TABLES `groups` WRITE;
/*!40000 ALTER TABLE `groups` DISABLE KEYS */;
INSERT INTO `groups` VALUES (10,'Blocked',0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0);
INSERT INTO `groups` VALUES (15,'Unactivated',0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0);
INSERT INTO `groups` VALUES (20,'Regular Member',0,0,0,0,0,1,0,0,0,0,0,0,1,1,0,1,0,1,0,0,0,0);
INSERT INTO `groups` VALUES (30,'Privileged',0,0,0,0,0,0,0,0,0,0,0,0,1,1,0,1,0,1,0,0,0,0);
INSERT INTO `groups` VALUES (33,'Contributor',0,0,0,0,0,0,0,0,0,0,0,0,1,1,0,1,0,1,0,0,0,0);
INSERT INTO `groups` VALUES (34,'Baller',0,0,0,0,0,0,0,0,0,0,0,0,1,1,0,1,0,1,0,0,0,0);
INSERT INTO `groups` VALUES (35,'Janitor',0,1,0,1,1,0,0,0,0,0,0,0,1,1,1,1,0,1,0,0,0,0);
INSERT INTO `groups` VALUES (40,'Mod',1,0,0,1,1,0,0,0,0,1,0,1,1,1,1,1,1,1,1,1,1,1);
INSERT INTO `groups` VALUES (50,'Administrator',1,1,1,1,1,0,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1);
/*!40000 ALTER TABLE `groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hit_counter`
--

DROP TABLE IF EXISTS `hit_counter`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hit_counter` (
  `count` bigint(20) unsigned DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hit_counter`
--

LOCK TABLES `hit_counter` WRITE;
/*!40000 ALTER TABLE `hit_counter` DISABLE KEYS */;
/*!40000 ALTER TABLE `hit_counter` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `logs`
--

DROP TABLE IF EXISTS `logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) DEFAULT '0',
  `ip` varchar(16) DEFAULT NULL,
  `action` varchar(255) DEFAULT NULL,
  `result` text,
  `date` timestamp NULL DEFAULT NULL,
  `cid` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `logs`
--

LOCK TABLES `logs` WRITE;
/*!40000 ALTER TABLE `logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notes`
--

DROP TABLE IF EXISTS `notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notes` (
  `id` int(32) NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `user_id` int(32) DEFAULT NULL,
  `x` int(32) NOT NULL,
  `y` int(32) NOT NULL,
  `width` int(32) NOT NULL,
  `height` int(32) NOT NULL,
  `angle` float NOT NULL DEFAULT '0',
  `ip` varchar(15) NOT NULL,
  `version` int(32) NOT NULL DEFAULT '1',
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  `post_id` int(32) NOT NULL,
  `body` text NOT NULL,
  `text_search_index` longtext,
  PRIMARY KEY (`id`),
  KEY `comments_text_search_idx` (`text_search_index`(10)),
  KEY `idx_notes__post` (`post_id`),
  KEY `notes_text_search_idx` (`text_search_index`(10))
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notes`
--

LOCK TABLES `notes` WRITE;
/*!40000 ALTER TABLE `notes` DISABLE KEYS */;
/*!40000 ALTER TABLE `notes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notes_history`
--

DROP TABLE IF EXISTS `notes_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notes_history` (
  `id` int(32) NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `x` int(32) NOT NULL,
  `y` int(32) NOT NULL,
  `width` int(32) NOT NULL,
  `height` int(32) NOT NULL,
  `angle` float NOT NULL DEFAULT '0',
  `body` text NOT NULL,
  `version` int(32) NOT NULL,
  `ip` varchar(15) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  `note_id` int(32) NOT NULL,
  `post_id` int(32) NOT NULL,
  `user_id` int(32) DEFAULT NULL,
  `text_search_index` longtext,
  PRIMARY KEY (`id`),
  KEY `idx_note_versions__post` (`post_id`),
  KEY `idx_notes__note` (`note_id`),
  KEY `index_note_versions_on_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notes_history`
--

LOCK TABLES `notes_history` WRITE;
/*!40000 ALTER TABLE `notes_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `notes_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pools`
--

DROP TABLE IF EXISTS `pools`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pools` (
  `id` int(32) NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `user_id` int(32) NOT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT '0',
  `post_count` int(32) NOT NULL DEFAULT '0',
  `description` text NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  `is_visible` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `pools_user_id_idx` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pools`
--

LOCK TABLES `pools` WRITE;
/*!40000 ALTER TABLE `pools` DISABLE KEYS */;
/*!40000 ALTER TABLE `pools` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pools_posts`
--

DROP TABLE IF EXISTS `pools_posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pools_posts` (
  `id` int(32) NOT NULL AUTO_INCREMENT,
  `sequence` int(32) NOT NULL DEFAULT '0',
  `pool_id` int(32) NOT NULL,
  `post_id` int(32) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `pools_posts_pool_id_idx` (`pool_id`),
  KEY `pools_posts_post_id_idx` (`post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pools_posts`
--

LOCK TABLES `pools_posts` WRITE;
/*!40000 ALTER TABLE `pools_posts` DISABLE KEYS */;
/*!40000 ALTER TABLE `pools_posts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `post_count`
--

DROP TABLE IF EXISTS `post_count`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `post_count` (
  `access_key` varchar(255) DEFAULT NULL,
  `pcount` bigint(20) unsigned DEFAULT '0',
  `last_update` varchar(255) DEFAULT NULL,
  KEY `access_key` (`access_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `post_count`
--

LOCK TABLES `post_count` WRITE;
/*!40000 ALTER TABLE `post_count` DISABLE KEYS */;
/*!40000 ALTER TABLE `post_count` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `post_votes`
--

DROP TABLE IF EXISTS `post_votes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `post_votes` (
  `id` int(32) NOT NULL AUTO_INCREMENT,
  `rated` varchar(4) NOT NULL,
  `ip` varchar(15) NOT NULL,
  `post_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `post_votes`
--

LOCK TABLES `post_votes` WRITE;
/*!40000 ALTER TABLE `post_votes` DISABLE KEYS */;
/*!40000 ALTER TABLE `post_votes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `posts`
--

DROP TABLE IF EXISTS `posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `posts` (
  `id` int(32) NOT NULL AUTO_INCREMENT,
  `creation_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `owner` int(32) DEFAULT NULL,
  `score` int(32) NOT NULL DEFAULT '0',
  `source` text NOT NULL,
  `title` text,
  `description` text,
  `hash` text NOT NULL,
  `last_comment` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `rating` char(1) NOT NULL DEFAULT 'e',
  `width` int(32) DEFAULT NULL,
  `height` int(32) DEFAULT NULL,
  `ip` varchar(15) NOT NULL,
  `tags` text NOT NULL,
  `ext` text NOT NULL,
  `parent` int(32) NOT NULL DEFAULT '0',
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `approver` int(32) DEFAULT NULL,
  `level` int(32) NOT NULL DEFAULT '15',
  `dnp` tinyint(1) NOT NULL DEFAULT '0',
  `spam` tinyint(1) NOT NULL DEFAULT '0',
  `spam_reason` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `index_posts_on_height` (`height`),
  KEY `index_posts_on_width` (`width`),
  KEY `post_status_idx` (`status`),
  KEY `idx_posts__created_at` (`creation_date`) USING BTREE,
  KEY `idx_posts__last_commented_at` (`last_comment`) USING BTREE,
  KEY `idx_posts__user` (`owner`) USING BTREE,
  KEY `idx_posts_parent_id` (`parent`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `posts`
--

LOCK TABLES `posts` WRITE;
/*!40000 ALTER TABLE `posts` DISABLE KEYS */;
/*!40000 ALTER TABLE `posts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `posts_tags`
--

DROP TABLE IF EXISTS `posts_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `posts_tags` (
  `post_id` int(32) NOT NULL,
  `tag_id` int(32) NOT NULL,
  KEY `idx_posts_tags__post` (`post_id`),
  KEY `idx_posts_tags__tag` (`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `posts_tags`
--

LOCK TABLES `posts_tags` WRITE;
/*!40000 ALTER TABLE `posts_tags` DISABLE KEYS */;
/*!40000 ALTER TABLE `posts_tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(55) DEFAULT NULL,
  `value` text,
  `updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key_UNIQUE` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tag_history`
--

DROP TABLE IF EXISTS `tag_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tag_history` (
  `total_amount` int(32) NOT NULL AUTO_INCREMENT,
  `id` int(32) NOT NULL,
  `tags` text NOT NULL,
  `user_id` int(32) DEFAULT NULL,
  `ip` varchar(15) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `rating` char(1) DEFAULT NULL,
  `version` bigint(20) unsigned NOT NULL DEFAULT '1',
  `active` bigint(1) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`total_amount`) USING BTREE,
  KEY `index_post_tag_histories_on_user_id` (`user_id`),
  KEY `idx_post_tag_histories__post` (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tag_history`
--

LOCK TABLES `tag_history` WRITE;
/*!40000 ALTER TABLE `tag_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `tag_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tags`
--

DROP TABLE IF EXISTS `tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tags` (
  `id` int(32) NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  `post_count` int(32) NOT NULL DEFAULT '0',
  `cached_related` text NOT NULL,
  `cached_related_expires_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `tag_type` smallint(16) NOT NULL DEFAULT '0',
  `is_ambiguous` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_tags__post_count` (`post_count`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tags`
--

LOCK TABLES `tags` WRITE;
/*!40000 ALTER TABLE `tags` DISABLE KEYS */;
/*!40000 ALTER TABLE `tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(32) NOT NULL AUTO_INCREMENT,
  `user` text NOT NULL,
  `pass` text NOT NULL,
  `ugroup` int(32) NOT NULL DEFAULT '0',
  `email` text NOT NULL,
  `my_tags` text NOT NULL,
  `invite_count` int(32) NOT NULL DEFAULT '0',
  `always_resize_images` tinyint(1) NOT NULL DEFAULT '0',
  `invited_by` int(32) DEFAULT NULL,
  `signup_date` datetime NOT NULL DEFAULT '1753-01-01 00:00:00',
  `last_logged_in_at` datetime NOT NULL DEFAULT '1753-01-01 00:00:00',
  `last_forum_topic_read_at` datetime NOT NULL DEFAULT '1960-01-01 00:00:00',
  `has_mail` tinyint(1) NOT NULL DEFAULT '0',
  `receive_dmails` tinyint(1) DEFAULT '0',
  `show_samples` tinyint(1) DEFAULT NULL,
  `title` text,
  `sig` text,
  `ip` varchar(15) DEFAULT NULL,
  `login_session` varchar(255) DEFAULT NULL,
  `mail_reset_code` text,
  `record_score` int(32) NOT NULL DEFAULT '0',
  `post_count` int(32) NOT NULL DEFAULT '0',
  `comment_count` int(32) NOT NULL DEFAULT '0',
  `tag_edit_count` int(32) NOT NULL DEFAULT '0',
  `forum_post_count` int(32) NOT NULL DEFAULT '0',
  `theme` varchar(45) NOT NULL DEFAULT 'default',
  `forum_can_post` int(1) NOT NULL DEFAULT '1',
  `forum_can_create_topic` int(1) NOT NULL DEFAULT '1',
  `notifications` tinyint(1) NOT NULL DEFAULT '0',
  `inf_scroll` tinyint(1) NOT NULL DEFAULT '0',
  `api_key` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','c0e7f2bd06b00a8667dcceffee15f1256da65067',50,'admin@nonexsitant123.com','',0,0,NULL,'2000-06-12 23:05:49','1753-01-01 00:00:00','1960-01-01 00:00:00',0,0,NULL,'50',NULL,NULL,NULL,NULL,0,0,0,0,0,'default',1,1,0,0);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wiki_page_versions`
--

DROP TABLE IF EXISTS `wiki_page_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wiki_page_versions` (
  `id` int(32) NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `version` int(32) NOT NULL DEFAULT '1',
  `title` text NOT NULL,
  `body` text NOT NULL,
  `user_id` int(32) DEFAULT NULL,
  `ip_addr` varchar(16) NOT NULL,
  `wiki_page_id` int(32) NOT NULL,
  `is_locked` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_wiki_page_versions__wiki_page` (`wiki_page_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wiki_page_versions`
--

LOCK TABLES `wiki_page_versions` WRITE;
/*!40000 ALTER TABLE `wiki_page_versions` DISABLE KEYS */;
/*!40000 ALTER TABLE `wiki_page_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wiki_pages`
--

DROP TABLE IF EXISTS `wiki_pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wiki_pages` (
  `id` int(32) NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `version` int(32) NOT NULL DEFAULT '1',
  `title` text NOT NULL,
  `body` text NOT NULL,
  `user_id` int(32) DEFAULT NULL,
  `ip_addr` varchar(16) NOT NULL,
  `is_locked` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_wiki_pages__updated_at` (`updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wiki_pages`
--

LOCK TABLES `wiki_pages` WRITE;
/*!40000 ALTER TABLE `wiki_pages` DISABLE KEYS */;
/*!40000 ALTER TABLE `wiki_pages` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
