
-- Create feedback table 

CREATE TABLE feedback (
id int(11) NOT NULL AUTO_INCREMENT,
name varchar(100) NOT NULL,
email varchar(150) NOT NULL,
phone varchar(20) DEFAULT NULL,
service varchar(100) NOT NULL,
rating int(11) NOT NULL,
feedback text NOT NULL,
newsletter tinyint(1) DEFAULT 0,
privacy tinyint(1) NOT NULL,
created_at timestamp NOT NULL DEFAULT current_timestamp(),
PRIMARY KEY (id)
);