
DROP VIEW IF EXISTS vw_upcoming_events CASCADE;

DROP TABLE IF EXISTS event_participants CASCADE;
DROP TABLE IF EXISTS events_archive CASCADE;
DROP TABLE IF EXISTS events CASCADE;
DROP TABLE IF EXISTS users CASCADE;
DROP TABLE IF EXISTS faculties CASCADE;
DROP TABLE IF EXISTS universities CASCADE;

DROP FUNCTION IF EXISTS check_event_date_before_join CASCADE;
DROP FUNCTION IF EXISTS archive_deleted_event CASCADE;
DROP FUNCTION IF EXISTS validate_future_event_date CASCADE;

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

-- WIDOK: Pokaż tylko nadchodzące wydarzenia z nazwami uczelni
CREATE VIEW vw_upcoming_events AS
SELECT e.id, e.title, e.date, u.name as university_name, f.name as faculty_name
FROM events e
JOIN universities u ON e.university_id = u.id
LEFT JOIN faculties f ON e.faculty_id = f.id
WHERE e.date >= NOW();

-- 1. Funkcja sprawdzająca datę
CREATE OR REPLACE FUNCTION check_event_date_before_join()
RETURNS TRIGGER AS $$
DECLARE
    event_date TIMESTAMP;
BEGIN
    -- Pobierz datę wydarzenia, do którego user chce dołączyć
    SELECT date INTO event_date FROM events WHERE id = NEW.event_id;

    -- Jeśli data wydarzenia jest wcześniejsza niż TERAZ, rzuć błąd
    IF event_date < NOW() THEN
        RAISE EXCEPTION 'Cannot join an event that has already taken place.';
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- 2. Trigger podpięty pod tabelę event_participants
CREATE TRIGGER trigger_prevent_joining_past_events
BEFORE INSERT ON event_participants
FOR EACH ROW
EXECUTE FUNCTION check_event_date_before_join();

-- 1. Tabela archiwalna (uproszczona kopia tabeli events)
CREATE TABLE events_archive (
    archive_id SERIAL PRIMARY KEY,
    original_event_id INT,
    title VARCHAR(255),
    deletion_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_by_user VARCHAR(50) -- Opcjonalnie, jeśli mamy info o userze w sesji DB
);

-- 2. Funkcja archiwizująca
CREATE OR REPLACE FUNCTION archive_deleted_event()
RETURNS TRIGGER AS $$
BEGIN
    INSERT INTO events_archive (original_event_id, title)
    VALUES (OLD.id, OLD.title);
    RETURN OLD;
END;
$$ LANGUAGE plpgsql;

-- 3. Trigger uruchamiany PO usunięciu
CREATE TRIGGER trigger_archive_events
AFTER DELETE ON events
FOR EACH ROW
EXECUTE FUNCTION archive_deleted_event();

-- 1. Funkcja walidująca przyszłą datę
CREATE OR REPLACE FUNCTION validate_future_event_date()
RETURNS TRIGGER AS $$
BEGIN
    IF NEW.date <= NOW() THEN
        RAISE EXCEPTION 'Event date must be in the future.';
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- 2. Trigger przy INSERT i UPDATE na tabeli events
CREATE TRIGGER trigger_validate_event_date
BEFORE INSERT OR UPDATE ON events
FOR EACH ROW
EXECUTE FUNCTION validate_future_event_date();