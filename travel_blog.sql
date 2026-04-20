-- Create database
CREATE DATABASE IF NOT EXISTS travel_blog CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Use the database
USE travel_blog;

-- Create users table (1st because it's referenced by other tables)
CREATE TABLE users (
    user_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    bio VARCHAR(255) DEFAULT NULL,
    profile_picture VARCHAR(255) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create categories table (2nd because posts references it)
CREATE TABLE categories (
    category_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create posts table (3rd because other tables reference it)
CREATE TABLE posts (
    post_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    category_id BIGINT NOT NULL,
    location VARCHAR(255) NOT NULL,
    image_path TEXT,
    likes INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (category_id) REFERENCES categories(category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Now create likes table (after posts table exists)
CREATE TABLE likes (
    like_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    post_id BIGINT NOT NULL,
    user_id BIGINT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_like (post_id, user_id),
    FOREIGN KEY (post_id) REFERENCES posts(post_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create photos table
CREATE TABLE photos (
    photo_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    post_id BIGINT NOT NULL,
    url VARCHAR(255) NOT NULL,
    description TEXT,
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(post_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create comments table
CREATE TABLE comments (
    comment_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    post_id BIGINT NOT NULL,
    user_id BIGINT NOT NULL,
    content TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(post_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create recommendations table
CREATE TABLE recommendations (
    recommendation_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    post_id BIGINT NOT NULL,
    type VARCHAR(50) NOT NULL,
    name VARCHAR(255),
    description TEXT,
    link VARCHAR(255),
    FOREIGN KEY (post_id) REFERENCES posts(post_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create bookmarks table
CREATE TABLE bookmarks (
    bookmark_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    post_id BIGINT NOT NULL,
    bookmarked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (post_id) REFERENCES posts(post_id),
    UNIQUE KEY unique_bookmark (user_id, post_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create follows table
CREATE TABLE follows (
    follower_id BIGINT,
    following_id BIGINT,
    followed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (follower_id) REFERENCES users(user_id),
    FOREIGN KEY (following_id) REFERENCES users(user_id),
    PRIMARY KEY (follower_id, following_id),
    INDEX idx_follower (follower_id),
    INDEX idx_following (following_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert initial categories
INSERT INTO categories (name, description) VALUES
('Adventure', 'Activities involving exploration or excitement'),
('Relaxation', 'Peaceful and calm experiences'),
('Cultural', 'Cultural and historical attractions'),
('Nature', 'Natural attractions and scenic places');

-- Insert sample users
INSERT INTO users (username, email, password, bio) VALUES 
('filip', 'filipdavcev@hotmail.com', '123456', 'Adventure seeker'),
('simona', 'simonazlatanovska@gmail.com', '123456', 'Nature lover');

-- Insert sample posts
INSERT INTO posts (user_id, title, content, category_id, location, image_path) 
VALUES 
(1, 'Exploring the Alps', 'An amazing journey through the Alps!', 4, 'Alps', 'alps.jpg'),
(2, 'Beach Relaxation in Bali', 'Enjoyed the serene beaches of Bali.', 2, 'Bali', 'bali.jpg');