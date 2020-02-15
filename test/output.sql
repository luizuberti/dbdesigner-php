SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS product;

DROP TABLE IF EXISTS onlineorderhasproduct;

DROP TABLE IF EXISTS onlineorder;

DROP TABLE IF EXISTS weblog;

DROP TABLE IF EXISTS webpageclick;

DROP TABLE IF EXISTS webserver;

DROP TABLE IF EXISTS onlinecustomer;

DROP TABLE IF EXISTS carthasproduct;

DROP TABLE IF EXISTS productgroup;

DROP TABLE IF EXISTS creditcard;

DROP TABLE IF EXISTS forumpost;

DROP TABLE IF EXISTS forumtopic;

CREATE TABLE product (
  idproduct INTEGER NOT NULL AUTO_INCREMENT COMMENT 'The AutoIncrement ID Field',
  idproductgroup INTEGER NOT NULL  ,
  name VARCHAR(42) NULL  COMMENT 'Contains the name of the Product',
  ean VARCHAR(20) NULL  COMMENT 'This is the european EAN code',
  price FLOAT(10,2) NULL  COMMENT 'The product price in Euro',
  info TEXT NULL  ,
  pic LONGBLOB NULL  ,
 PRIMARY KEY (idproduct),
 INDEX product_name(name),
 UNIQUE INDEX product_ean(ean),
  FOREIGN KEY(idproductgroup)
    REFERENCES productgroup(idproductgroup)
      ON DELETE RESTRICT
      ON UPDATE RESTRICT)
ENGINE=InnoDB ;
INSERT INTO product(idproduct, idproductgroup, name, ean, price, pic)
VALUES(1, 1, 'Learning C++', '154365423', 42.23, NULL);

INSERT INTO product(idproduct, idproductgroup, name, ean, price, pic)
VALUES(2, 1, 'Lord of the Rings - Part I', '437634323', 23.15, NULL);

INSERT INTO product(idproduct, idproductgroup, name, ean, price, pic)
VALUES(3, 1, 'Alice in Wonderland', '34631764345', 14.20, NULL);

CREATE TABLE onlineorderhasproduct (
  idonlineorder INTEGER NOT NULL  ,
  idproduct INTEGER NOT NULL  ,
 PRIMARY KEY (idonlineorder),
 INDEX onlineorderhasproduct_FKIndex1(idonlineorder),
  FOREIGN KEY(idproduct)
    REFERENCES product(idproduct)
      ON DELETE CASCADE
      ON UPDATE CASCADE,
  FOREIGN KEY(idonlineorder)
    REFERENCES onlineorder(idonlineorder)
      ON DELETE CASCADE
      ON UPDATE CASCADE)
ENGINE=InnoDB ;
INSERT INTO onlineorderhasproduct(idonlineorder, idproduct) VALUES(1, 1);

INSERT INTO onlineorderhasproduct(idonlineorder, idproduct) VALUES(2, 2); 

CREATE TABLE onlineorder (
  idonlineorder INTEGER NOT NULL AUTO_INCREMENT ,
  idonlinecustomer INTEGER NOT NULL  ,
  date DATETIME NULL  ,
  shippingaddress TEXT NULL  ,
 PRIMARY KEY (idonlineorder),
  FOREIGN KEY(idonlinecustomer)
    REFERENCES onlinecustomer(idonlinecustomer)
      ON DELETE RESTRICT
      ON UPDATE RESTRICT)
ENGINE=InnoDB ;
INSERT INTO onlineorder(idonlineorder, idonlinecustomer, date, shippingaddress) 
VALUES(1, 1, '2003-04-23', 'Same as billing address');

CREATE TABLE weblog (
  idweblog INTEGER NOT NULL  ,
  idwebserver INTEGER NOT NULL  ,
  date DATETIME NULL  ,
  action INTEGER NULL  ,
  ip VARCHAR(20) NULL  ,
 PRIMARY KEY (idweblog),
  FOREIGN KEY(idwebserver)
    REFERENCES webserver(idwebserver)
      ON DELETE RESTRICT
      ON UPDATE RESTRICT)
ENGINE=InnoDB ;


CREATE TABLE webpageclick (
  idwebclick INTEGER NOT NULL  ,
  iduser INTEGER NULL  ,
  clickdate DATETIME NULL  ,
  link VARCHAR(255) NULL  ,
 PRIMARY KEY (idwebclick))
ENGINE=InnoDB ;


CREATE TABLE webserver (
  idwebserver INTEGER NOT NULL  ,
  name VARCHAR(20) NULL  ,
 PRIMARY KEY (idwebserver))
ENGINE=InnoDB ;


CREATE TABLE onlinecustomer (
  idonlinecustomer INTEGER NOT NULL AUTO_INCREMENT ,
  idcreditcard INTEGER NOT NULL  ,
  name VARCHAR(30) NULL  ,
  address1 VARCHAR(80) NULL  ,
  address2 VARCHAR(80) NULL  ,
  region VARCHAR(42) NULL  ,
  city VARCHAR(42) NULL  ,
  zip VARCHAR(6) NULL  ,
  phone VARCHAR(20) NULL  ,
  creditcardnr VARCHAR(20) NULL  ,
  creditcarddate DATE NULL  ,
 PRIMARY KEY (idonlinecustomer),
  FOREIGN KEY(idcreditcard)
    REFERENCES creditcard(idcreditcard)
      ON DELETE NO ACTION
      ON UPDATE NO ACTION)
ENGINE=InnoDB COMMENT 'This Table stores all Online Customers.';
INSERT INTO onlinecustomer(idonlinecustomer, idcreditcard, name, address1, address2, 
 region, city, zip, phone, creditcardnr, creditcarddate) 
VALUES(1, 1, 'Jack Foley', 'Goodthings Inc.', 'Uptown Street 4', 
 'US', 'New York', '12345', '0243 43 543', '1234 3412 2432 3341', '2004-03-01');

INSERT INTO onlinecustomer(idonlinecustomer, idcreditcard, name, address1, address2,  
 region, city, zip, phone, creditcardnr, creditcarddate)  
VALUES(2, 2, 'Ray Berger', 'Pensilvaniastr.5', '',  
 'US', 'Washington', '54321', '0543 639 53', '8641 3853 2964 2853', '2003-07-01');

CREATE TABLE carthasproduct (
  idonlinecustomer INTEGER NOT NULL  ,
  idproduct INTEGER NOT NULL  ,
 PRIMARY KEY (idonlinecustomer),
 INDEX carthasproduct_FKIndex1(idonlinecustomer),
  FOREIGN KEY(idonlinecustomer)
    REFERENCES onlinecustomer(idonlinecustomer)
      ON DELETE RESTRICT
      ON UPDATE RESTRICT,
  FOREIGN KEY(idproduct)
    REFERENCES product(idproduct)
      ON DELETE RESTRICT
      ON UPDATE RESTRICT)
ENGINE=InnoDB ;
INSERT INTO carthasproduct(idonlinecustomer, idproduct) VALUES(1, 3);

INSERT INTO carthasproduct(idonlinecustomer, idproduct) VALUES(2, 2); 

CREATE TABLE productgroup (
  idproductgroup INTEGER NOT NULL AUTO_INCREMENT ,
  groupname VARCHAR(42) NULL  ,
 PRIMARY KEY (idproductgroup))
ENGINE=InnoDB ;
INSERT INTO productgroup(idproductgroup, groupname) VALUES(1, 'Books');

INSERT INTO productgroup(idproductgroup, groupname) VALUES(2, 'DVDs'); 

INSERT INTO productgroup(idproductgroup, groupname) VALUES(3, 'Software'); 

CREATE TABLE creditcard (
  idcreditcard INTEGER NOT NULL AUTO_INCREMENT ,
  company VARCHAR(42) NULL  ,
 PRIMARY KEY (idcreditcard))
ENGINE=InnoDB ;
INSERT INTO creditcard(idcreditcard, company) VALUES(1, 'VISA');

INSERT INTO creditcard(idcreditcard, company) VALUES(2, 'Mastercard'); 

CREATE TABLE forumpost (
  idforumpost INTEGER NOT NULL  ,
  idforumtopic INTEGER NOT NULL  ,
  idforumpost_parent INTEGER NOT NULL  ,
  idonlinecustomer INTEGER NOT NULL  ,
  title VARCHAR(45) NULL  ,
  paragraphs TEXT NULL  ,
  createdate DATETIME NULL  ,
 PRIMARY KEY (idforumpost),
  FOREIGN KEY(idonlinecustomer)
    REFERENCES onlinecustomer(idonlinecustomer)
      ON DELETE NO ACTION
      ON UPDATE NO ACTION,
  FOREIGN KEY(idforumpost_parent)
    REFERENCES forumpost(idforumpost)
      ON DELETE NO ACTION
      ON UPDATE NO ACTION,
  FOREIGN KEY(idforumtopic)
    REFERENCES forumtopic(idforumtopic)
      ON DELETE NO ACTION
      ON UPDATE NO ACTION)
ENGINE=InnoDB ;


CREATE TABLE forumtopic (
  idforumtopic INTEGER NOT NULL  ,
  title VARCHAR(80) NULL  ,
 PRIMARY KEY (idforumtopic))
ENGINE=InnoDB ;



SET FOREIGN_KEY_CHECKS=1;
