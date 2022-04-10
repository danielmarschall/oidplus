
-- It is important that table names and column names inside quotes are uppercase
-- because if you query a name without quotes, then it will be automatically
-- converted to uppercase. Example:
-- create table test     ==> uppercase name will be written to disk
-- create table "TEST"   ==> uppercase name will be written to disk
-- create table "test"   ==> lowercase name will be written to disk
-- select * from test    ==> uppercase name will be read
-- select * from "TEST"  ==> uppercase name will be read
-- select * from "test"  ==> lowercase name will be read

-- The column name "COMMENT" is a reserved word.
-- In the program we MUST use 'select "COMMENT"' instead of 'select comment'

DROP TABLE "CONFIG";
CREATE TABLE "CONFIG" (
  "NAME" VARCHAR2(50) NOT NULL,
  "VALUE" VARCHAR2(4000) NOT NULL,
  "DESCRIPTION" VARCHAR2(255),
  "PROTECTED" NUMBER(1) DEFAULT '0' NOT NULL,
  "VISIBLE" NUMBER(1) DEFAULT '0' NOT NULL,
  PRIMARY KEY("NAME")
);

/* -------------------------------------------------- */

DROP TABLE "ASN1ID";
CREATE TABLE "ASN1ID" (
  "LFD" NUMBER GENERATED ALWAYS AS IDENTITY,
  "OID" VARCHAR2(255) NOT NULL,
  "NAME" VARCHAR2(255) NOT NULL,
  "STANDARDIZED" NUMBER(1) DEFAULT '0' NOT NULL,
  "WELL_KNOWN" NUMBER(1) DEFAULT '0' NOT NULL,
  PRIMARY KEY ("LFD"),
  CONSTRAINT "UNIQ_ASN1ID" UNIQUE ("OID","NAME")
);

/* -------------------------------------------------- */

DROP TABLE "IRI";
CREATE TABLE "IRI" (
  "LFD" NUMBER GENERATED ALWAYS AS IDENTITY,
  "OID" VARCHAR2(255) NOT NULL,
  "NAME" VARCHAR2(255) NOT NULL,
  "LONGARC" NUMBER(1) DEFAULT '0' NOT NULL,
  "WELL_KNOWN" NUMBER(1) DEFAULT '0' NOT NULL,
  PRIMARY KEY ("LFD"),
  CONSTRAINT "UNIQ_IRI" UNIQUE ("OID","NAME")
);

/* -------------------------------------------------- */

DROP TABLE "OBJECTS";
CREATE TABLE "OBJECTS" (
  "ID" VARCHAR2(255) NOT NULL,
  "PARENT" VARCHAR2(255) DEFAULT NULL,
  "TITLE" VARCHAR2(255) NULL,
  "DESCRIPTION" VARCHAR2(4000) NULL,
  "RA_EMAIL" VARCHAR2(100) NULL,
  "CONFIDENTIAL" NUMBER(1) NOT NULL,
  "CREATED" TIMESTAMP,
  "UPDATED" TIMESTAMP,
  "COMMENT" VARCHAR2(255) NULL,
  PRIMARY KEY ("ID")
);

CREATE INDEX "OBJECTS_PARENT" ON "OBJECTS"("PARENT");
CREATE INDEX "OBJECTS_RA_EMAIL" ON "OBJECTS"("RA_EMAIL");

/* -------------------------------------------------- */

DROP TABLE "RA";
CREATE TABLE "RA" (
  "RA_ID" NUMBER GENERATED ALWAYS AS IDENTITY,
  "EMAIL" VARCHAR2(100) NOT NULL,
  "RA_NAME" VARCHAR2(100) NULL,
  "PERSONAL_NAME" VARCHAR2(100) NULL,
  "ORGANIZATION" VARCHAR2(100) NULL,
  "OFFICE" VARCHAR2(100) NULL,
  "STREET" VARCHAR2(100) NULL,
  "ZIP_TOWN" VARCHAR2(100) NULL,
  "COUNTRY" VARCHAR2(100) NULL,
  "PHONE" VARCHAR2(100) NULL,
  "MOBILE" VARCHAR2(100) NULL,
  "FAX" VARCHAR2(100) NULL,
  "PRIVACY" NUMBER(1) DEFAULT '0' NOT NULL,
  "SALT" VARCHAR2(100) NULL,
  "AUTHKEY" VARCHAR2(100) NULL,
  "REGISTERED" TIMESTAMP,
  "UPDATED" TIMESTAMP,
  "LAST_LOGIN" TIMESTAMP,
  PRIMARY KEY ("RA_ID"),
  CONSTRAINT "UNIQ_RA" UNIQUE ("EMAIL")
);

/* -------------------------------------------------- */

DROP TABLE "LOG";
CREATE TABLE "LOG" (
  "ID" NUMBER GENERATED ALWAYS AS IDENTITY,
  "UNIX_TS" NUMBER NOT NULL,
  "ADDR" VARCHAR2(45) NOT NULL,
  "EVENT" VARCHAR2(4000) NOT NULL,
  PRIMARY KEY ("ID")
);

/* -------------------------------------------------- */

DROP TABLE "LOG_USER";
CREATE TABLE "LOG_USER" (
  "ID" NUMBER GENERATED ALWAYS AS IDENTITY,
  "LOG_ID" NUMBER NOT NULL,
  "USERNAME" VARCHAR2(255) NOT NULL,
  "SEVERITY" NUMBER NOT NULL,
  PRIMARY KEY ("ID"),
  CONSTRAINT "UNIQ_LOG_USER" UNIQUE ("LOG_ID","USERNAME")
);

CREATE INDEX "LOG_USER_LOG_ID" ON "LOG_USER"("LOG_ID");
CREATE INDEX "LOG_USER_USERNAME" ON "LOG_USER"("USERNAME");

/* -------------------------------------------------- */

DROP TABLE "LOG_OBJECT";
CREATE TABLE "LOG_OBJECT" (
  "ID" NUMBER GENERATED ALWAYS AS IDENTITY,
  "LOG_ID" NUMBER NOT NULL,
  "OBJECT" VARCHAR2(255) NOT NULL,
  "SEVERITY" NUMBER NOT NULL,
  PRIMARY KEY ("ID"),
  CONSTRAINT "UNIQ_LOG_OBJECT" UNIQUE ("LOG_ID","OBJECT")
);

CREATE INDEX "LOG_OBJECT_LOG_ID" ON "LOG_OBJECT"("LOG_ID");
CREATE INDEX "LOG_OBJECT_OBJECT" ON "LOG_OBJECT"("OBJECT");

/* -------------------------------------------------- */

INSERT INTO "CONFIG" ("NAME", "DESCRIPTION", "VALUE", "PROTECTED", "VISIBLE") VALUES ('database_version', 'Version of the database tables', '1000', '1', '0');
