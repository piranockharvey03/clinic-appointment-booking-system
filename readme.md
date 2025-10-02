This is Clinic appointment System that patients can use to book appointments in hospitals.

How to run in locally on your laptop.

download the zipped folder, extract it and then copy and paste in the htdocs folder which is under the install directory
you used to install xammp.

Please install xammp software.

download the zipped folder of the system, extract it and then copy and paste in the htdocs folder which is under the install directory you used to install xammp.

open xampp and on the panel click the start buttons for phpmyadmin and mysql
Then click on the CMD button(black in color on the right)

Input these following commands:

cd mysql

mysql -h localhost -u root -p

//then press enter when prompt to enter a password or input a password if you had already set one before. the run the next command below//

CREATE DATABASE medicare;

USE medicare;

CREATE TABLE users (
id INT AUTO_INCREMENT PRIMARY KEY,
full_name VARCHAR(100) NOT NULL,
email VARCHAR(100) NOT NULL UNIQUE,
phone VARCHAR(20) NOT NULL,
password VARCHAR(255) NOT NULL,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

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

After running those sql commands, then go to ur browser and then acces the website using the path
(http://localhost/hospital/index.html)
