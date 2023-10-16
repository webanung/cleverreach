#
# Table structure for table 'tx_officialcleverreach_domain_model_process'
#
CREATE TABLE tx_officialcleverreach_domain_model_process (

  uid      INT(11)             NOT NULL AUTO_INCREMENT,
  pid      INT(11) DEFAULT '0' NOT NULL,

  guid     VARCHAR(50)         NOT NULL,
  runner   VARCHAR(500)        NOT NULL,

  PRIMARY KEY (uid),
  KEY parent (pid)
);

#
# Table structure for table 'tx_officialcleverreach_domain_model_configuration'
#
CREATE TABLE tx_officialcleverreach_domain_model_configuration (

  uid      INT(11)             NOT NULL AUTO_INCREMENT,
  pid      INT(11) DEFAULT '0' NOT NULL,

  cr_key   VARCHAR(50)         NULL,
  cr_value TEXT                NULL,

  PRIMARY KEY (uid),
  KEY parent (pid)
);

CREATE TABLE tx_officialcleverreach_domain_model_queue (
  uid                   INT(11)             NOT NULL AUTO_INCREMENT,
  pid                   INT(11) DEFAULT '0' NOT NULL,
  status                VARCHAR(30)         NOT NULL,
  type                  VARCHAR(100)        NOT NULL,
  queueName             VARCHAR(50)         NOT NULL,
  progress              INT(11)             NOT NULL DEFAULT '0',
  retries               TINYINT(4)          NOT NULL DEFAULT '0',
  failureDescription    TEXT,
  serializedTask        MEDIUMTEXT          NOT NULL,
  createTimestamp       INT(11)                      DEFAULT NULL,
  queueTimestamp        INT(11)                      DEFAULT NULL,
  lastUpdateTimestamp   INT(11)                      DEFAULT NULL,
  startTimestamp        INT(11)                      DEFAULT NULL,
  finishTimestamp       INT(11)                      DEFAULT NULL,
  failTimestamp         INT(11)                      DEFAULT NULL,
  lastExecutionProgress INT(11)             NOT NULL,
  PRIMARY KEY (uid),
  KEY parent (pid)
);

CREATE TABLE fe_users (
  cr_newsletter_subscription tinyint(3) DEFAULT NULL
);
