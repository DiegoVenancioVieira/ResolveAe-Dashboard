-- init.sql
CREATE DATABASE IF NOT EXISTS glpi_db;
USE glpi_db;

-- 1. Users
CREATE TABLE glpi_users (
  id int(11) NOT NULL AUTO_INCREMENT,
  name varchar(255) DEFAULT NULL,
  realname varchar(255) DEFAULT NULL,
  firstname varchar(255) DEFAULT NULL,
  PRIMARY KEY (id)
);

INSERT INTO glpi_users (id, name, realname, firstname) VALUES
(1, 'glpi', 'Admin', 'Sistema'),
(2, 'mario', 'Mario', 'Bros'),
(3, 'luigi', 'Luigi', 'Bros'),
(4, 'peach', 'Peach', 'Toadstool'),
(5, 'bowser', 'Bowser', 'King');

-- 2. Entities (Departments/Clients)
CREATE TABLE glpi_entities (
  id int(11) NOT NULL AUTO_INCREMENT,
  name varchar(255) DEFAULT NULL,
  completename varchar(255) DEFAULT NULL,
  PRIMARY KEY (id)
);

INSERT INTO glpi_entities (id, name, completename) VALUES
(0, 'Root Entity', 'Root Entity'),
(1, 'Matriz', 'Matriz'),
(2, 'Filial SP', 'Matriz > Filial SP'),
(3, 'Filial RJ', 'Matriz > Filial RJ');

-- 3. Categories
CREATE TABLE glpi_itilcategories (
  id int(11) NOT NULL AUTO_INCREMENT,
  name varchar(255) DEFAULT NULL,
  completename varchar(255) DEFAULT NULL,
  PRIMARY KEY (id)
);

INSERT INTO glpi_itilcategories (id, name, completename) VALUES
(1, 'Hardware', 'Hardware'),
(2, 'Software', 'Software'),
(3, 'Rede', 'Rede'),
(4, 'Impressoras', 'Impressoras');

-- 4. Tickets
CREATE TABLE glpi_tickets (
  id int(11) NOT NULL AUTO_INCREMENT,
  entities_id int(11) NOT NULL DEFAULT '0',
  name varchar(255) DEFAULT NULL,
  date datetime DEFAULT NULL,
  closedate datetime DEFAULT NULL,
  solvedate datetime DEFAULT NULL,
  status int(11) NOT NULL DEFAULT '1', -- 1=Novo, 2=Attr, 3=Plan, 4=Pend, 5=Soluc, 6=Fechado
  users_id_recipient int(11) NOT NULL DEFAULT '0',
  urgency int(11) NOT NULL DEFAULT '3',
  impact int(11) NOT NULL DEFAULT '3',
  priority int(11) NOT NULL DEFAULT '3',
  itilcategories_id int(11) NOT NULL DEFAULT '0',
  type int(11) NOT NULL DEFAULT '1', -- 1=Incidente, 2=Requisicao
  time_to_resolve datetime DEFAULT NULL,
  PRIMARY KEY (id)
);

-- Insert tickets (dynamic dates for dashboard)
INSERT INTO glpi_tickets (entities_id, name, date, status, priority, itilcategories_id, users_id_recipient) VALUES
(1, 'PC nao liga', NOW(), 1, 5, 1, 4),
(1, 'Erro no Excel', NOW(), 2, 3, 2, 4),
(2, 'Internet lenta', DATE_SUB(NOW(), INTERVAL 1 HOUR), 2, 4, 3, 5),
(2, 'Impressora travada', DATE_SUB(NOW(), INTERVAL 2 HOUR), 5, 3, 4, 3),
(3, 'Mouse quebrado', DATE_SUB(NOW(), INTERVAL 1 DAY), 6, 2, 1, 2),
(1, 'Instalar VS Code', DATE_SUB(NOW(), INTERVAL 30 MINUTE), 1, 2, 2, 3);

-- 5. Ticket-User links (assignees)
CREATE TABLE glpi_tickets_users (
  id int(11) NOT NULL AUTO_INCREMENT,
  tickets_id int(11) NOT NULL DEFAULT '0',
  users_id int(11) NOT NULL DEFAULT '0',
  type int(11) NOT NULL DEFAULT '1', -- 1=Req, 2=Tecnico, 3=Obs
  PRIMARY KEY (id)
);

INSERT INTO glpi_tickets_users (tickets_id, users_id, type) VALUES
(2, 2, 2), -- Mario handles ticket 2
(3, 3, 2), -- Luigi handles ticket 3
(4, 2, 2); -- Mario handles ticket 4

-- 6. Satisfaction (CSAT)
CREATE TABLE glpi_ticketsatisfactions (
  id int(11) NOT NULL AUTO_INCREMENT,
  tickets_id int(11) NOT NULL DEFAULT '0',
  date_answered datetime DEFAULT NULL,
  satisfaction int(11) DEFAULT NULL,
  PRIMARY KEY (id)
);

INSERT INTO glpi_ticketsatisfactions (tickets_id, date_answered, satisfaction) VALUES
(5, NOW(), 5);
