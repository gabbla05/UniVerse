-- 1. UCZELNIE
CREATE TABLE universities (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    city VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. WYDZIAŁY (Relacja 1:N z Uczelnią)
CREATE TABLE faculties (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    university_id INT NOT NULL,
    FOREIGN KEY (university_id) REFERENCES universities (id) ON DELETE CASCADE
);

-- 3. UŻYTKOWNICY (Studenci/Admini)
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    surname VARCHAR(100) NOT NULL,
    student_id VARCHAR(50), -- nr albumu
    role VARCHAR(20) DEFAULT 'user' CHECK (role IN ('app_admin', 'uni_admin', 'user')),
    university_id INT,
    faculty_id INT,
    FOREIGN KEY (university_id) REFERENCES universities (id) ON DELETE SET NULL,
    FOREIGN KEY (faculty_id) REFERENCES faculties (id) ON DELETE SET NULL
);

-- 4. WYDARZENIA
CREATE TABLE events (
    id SERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    date TIMESTAMP NOT NULL,
    location VARCHAR(255),
    image_url VARCHAR(255),
    category VARCHAR(50), -- Party, Workshop, Sport
    creator_id INT NOT NULL,
    university_id INT NOT NULL,
    faculty_id INT, -- NULL oznacza wydarzenie dla całej uczelni
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (creator_id) REFERENCES users (id),
    FOREIGN KEY (university_id) REFERENCES universities (id) ON DELETE CASCADE,
    FOREIGN KEY (faculty_id) REFERENCES faculties (id) ON DELETE CASCADE
);

-- 5. UCZESTNICTWO (Relacja N:M - Student <-> Wydarzenie)
CREATE TABLE event_participants (
    user_id INT NOT NULL,
    event_id INT NOT NULL,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, event_id),
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE CASCADE
);

-- WIDOK (Wymóg projektu): Pokaż tylko nadchodzące wydarzenia z nazwami uczelni
CREATE VIEW vw_upcoming_events AS
SELECT e.id, e.title, e.date, u.name as university_name, f.name as faculty_name
FROM events e
JOIN universities u ON e.university_id = u.id
LEFT JOIN faculties f ON e.faculty_id = f.id
WHERE e.date >= NOW();

-- DANE TESTOWE (Żebyś miała na czym klikać)
INSERT INTO universities (name, city) VALUES ('Politechnika Krakowska', 'Kraków');
INSERT INTO faculties (name, university_id) VALUES ('Wydział Informatyki i Telekomunikacji', 1);
-- Hasło to 'admin' (hashowane md5 dla testu, w produkcji użyjemy password_hash w PHP)
INSERT INTO users (email, password, name, surname, role, university_id, faculty_id) 
VALUES ('admin@pk.edu.pl', '21232f297a57a5a743894a0e4a801fc3', 'Jan', 'Admin', 'uni_admin', 1, 1);