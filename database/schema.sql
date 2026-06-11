-- Create Database if not exists
CREATE DATABASE IF NOT EXISTS `travel_agency`;
USE `travel_agency`;

-- Table structure for table `users`
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('user', 'admin') DEFAULT 'user',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `packages`
CREATE TABLE IF NOT EXISTS `packages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `location` VARCHAR(100) NOT NULL,
  `description` TEXT NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `duration` VARCHAR(50) NOT NULL,
  `image_url` VARCHAR(512) NOT NULL,
  `rating` DECIMAL(2,1) DEFAULT 5.0,
  `featured` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `bookings`
CREATE TABLE IF NOT EXISTS `bookings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `package_id` INT NOT NULL,
  `booking_date` DATE NOT NULL,
  `travel_date` DATE NOT NULL,
  `guests` INT NOT NULL,
  `total_price` DECIMAL(10,2) NOT NULL,
  `status` ENUM('pending', 'approved', 'cancelled') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`package_id`) REFERENCES `packages`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `wishlist`
CREATE TABLE IF NOT EXISTS `wishlist` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `package_id` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`package_id`) REFERENCES `packages`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `user_package` (`user_id`, `package_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed Default Users
-- admin@travel.com / Password: AdminPassword123 (hashed using PASSWORD_DEFAULT)
-- user@travel.com / Password: UserPassword123 (hashed using PASSWORD_DEFAULT)
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`) VALUES
(1, 'System Admin', 'admin@travel.com', '$2y$10$OAwNpeoY1klyBq5m0gDqUutZ6D13k.7eUptvYf5j6c0t6K/71wY0i', 'admin'),
(2, 'John Doe', 'user@travel.com', '$2y$10$428vI5s2.Hk0KkF3o3g4/ePq9z1636rZ2t6q5g3W9i68wY2t10wYe', 'user')
ON DUPLICATE KEY UPDATE `email` = VALUES(`email`);

-- Seed Packages
INSERT INTO `packages` (`id`, `title`, `location`, `description`, `price`, `duration`, `image_url`, `rating`, `featured`) VALUES
(1, 'Tropical Bali Retreat', 'Bali, Indonesia', 'Immerse yourself in the breathtaking beauty of Bali. Explore ancient temples, walk along scenic emerald rice terraces, and relax on pristine sun-kissed beaches. This luxury getaway covers resort stays, private guided temple tours, snorkeling in crystal-clear waters, and gourmet dining experiences designed to rejuvenate your senses.', 650.00, '5 Days, 4 Nights', 'https://images.unsplash.com/photo-1537996194471-e657df975ab4?auto=format&fit=crop&w=800&q=80', 4.8, 1),
(2, 'Romantic Parisian Escape', 'Paris, France', 'Experience the magic of the City of Lights. Wander down the historic cobblestones of Montmartre, enjoy an exclusive private cruise along the Seine at sunset, and dine in style on the first level of the Eiffel Tower. Includes boutique hotel stay, daily Parisian breakfasts, and museum passes to beat the queues at the Louvre.', 1200.00, '7 Days, 6 Nights', 'https://images.unsplash.com/photo-1502602898657-3e91760cbb34?auto=format&fit=crop&w=800&q=80', 4.9, 1),
(3, 'Tokyo & Kyoto Cultural Tour', 'Tokyo, Japan', 'Discover the captivating contrast of ancient traditions and futuristic innovation. Visit Tokyo\'s soaring neon districts, experience a traditional tea ceremony in historic Kyoto, and capture the iconic Mount Fuji from beautiful Lake Kawaguchi. Seamless travel is guaranteed with bullet train (Shinkansen) passes included.', 950.00, '6 Days, 5 Nights', 'https://images.unsplash.com/photo-1493976040374-85c8e12f0c0e?auto=format&fit=crop&w=800&q=80', 4.7, 1),
(4, 'Imperial Rome & Vatican Tour', 'Rome, Italy', 'Step back in time to the cradle of Western civilization. Walk the legendary floors of the Colosseum, marvel at the celestial Sistine Chapel in the Vatican, and throw a coin into the spectacular Trevi Fountain. Savor authentic Neapolitan pizza and Italian gelato with our local culinary guides.', 800.00, '5 Days, 4 Nights', 'https://images.unsplash.com/photo-1552832230-c0197dd311b5?auto=format&fit=crop&w=800&q=80', 4.6, 1),
(5, 'Swiss Alps Majestic Peaks', 'Zermatt, Switzerland', 'Breathe in the pristine alpine air of the iconic Matterhorn. Embark on scenic train journeys on the famous Glacier Express, hike along magnificent snow-peaked valleys, and unwind in world-class thermal spas. Includes cozy mountain resort lodges, guided ski lessons, and premium fondue experiences.', 1400.00, '6 Days, 5 Nights', 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?auto=format&fit=crop&w=800&q=80', 4.9, 1),
(6, 'Maldives Luxury Overwater Villa', 'Maafushi, Maldives', 'Indulge in pure tropical luxury in a private overwater villa suspended above turquoise lagoons. This premium all-inclusive resort stay offers private plunge pools, vibrant coral reef diving, sunset dolphin cruises, and romantic candlelit dinners under the starlit sky. The ultimate tropical island paradise.', 1100.00, '4 Days, 3 Nights', 'https://images.unsplash.com/photo-1439066615861-d1af74d74000?auto=format&fit=crop&w=800&q=80', 5.0, 1),
(7, 'Icelandic Northern Lights Adventure', 'Reykjavik, Iceland', 'Embark on a mythical voyage to the land of fire and ice. Hunt for the magical Aurora Borealis across dark winter skies, soak in the therapeutic geothermal waters of the Blue Lagoon, and view roaring waterfalls and active geysers along the legendary Golden Circle route.', 1350.00, '5 Days, 4 Nights', 'https://images.unsplash.com/photo-1504893524553-ac55fce698be?auto=format&fit=crop&w=800&q=80', 4.8, 0),
(8, 'Grand Egyptian Pyramids Tour', 'Cairo, Egypt', 'Unearth the mysteries of ancient Pharaohs. Stand in awe before the majestic Pyramids of Giza and the Sphinx, sail the tranquil Nile River on a traditional felucca, and explore the golden treasures of King Tutankhamun at the Egyptian Museum. Guided by certified Egyptologists throughout.', 750.00, '6 Days, 5 Nights', 'https://images.unsplash.com/photo-1503177119275-0aa32b3a9368?auto=format&fit=crop&w=800&q=80', 4.5, 0)
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`);
