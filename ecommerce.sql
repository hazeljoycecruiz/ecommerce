
CREATE TABLE `users` (
  `user_id` CHAR(36) NOT NULL DEFAULT uuid(),
  `first_name` VARCHAR(50) DEFAULT NULL,
  `last_name` VARCHAR(50) DEFAULT NULL,
  `email` VARCHAR(100) UNIQUE NOT NULL,
  `password` TEXT NOT NULL,
  `phone_num` BIGINT(20) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `lang_profile` TEXT DEFAULT NULL,
  `role_id` TINYINT UNSIGNED NOT NULL CHECK (`role_id` IN (0, 1, 2)),
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_verified` TINYINT(1) DEFAULT 0,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `cancel_orders` (
  `cancel_order_id` int(11) NOT NULL,
  `order_id` char(36) DEFAULT NULL,
  `reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `cart_items` (
  `cart_item_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT NULL,
  `product_id` char(36) DEFAULT NULL,
  `cart_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `logs` (
  `log_id` int(11) NOT NULL,
  `action` enum('INSERT','UPDATE','DELETE') DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_id` char(36) DEFAULT NULL,
  `order_id` char(36) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `media` (
  `media_id` int(11) NOT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `product_id` char(36) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `orders` (
  `order_id` char(36) NOT NULL DEFAULT uuid(),
  `total_amount` decimal(10,2) DEFAULT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `shipping_address` text DEFAULT NULL,
  `expected_delivery_date` date DEFAULT NULL,
  `status` enum('processed','cancelled','delivered') DEFAULT NULL,
  `user_id` char(36) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `order_items` (
  `order_item_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `product_id` char(36) DEFAULT NULL,
  `order_id` char(36) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `mode` enum('credit','debit','COD','GCash') DEFAULT NULL,
  `status` enum('paid','to pay') DEFAULT NULL,
  `order_id` char(36) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `permissions` (
  `permission_id` int(11) NOT NULL,
  `can_edit` tinyint(1) DEFAULT NULL,
  `can_delete` tinyint(1) DEFAULT NULL,
  `can_update` tinyint(1) DEFAULT NULL,
  `role_id` int(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `products` (
  `product_id` char(36) NOT NULL DEFAULT uuid(),
  `product_name` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `stock_quantity` int(11) DEFAULT NULL,
  `category` enum('shorts','pants','t-shirts','shoes','hats') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `product_specs` (
  `spec_id` int(11) NOT NULL,
  `type_of_specs` varchar(100) DEFAULT NULL,
  `value` varchar(255) DEFAULT NULL,
  `product_id` char(36) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL,
  `rating` int(11) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `review_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `product_id` char(36) DEFAULT NULL,
  `user_id` char(36) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `roles` (
  `role_id` int(20) UNSIGNED NOT NULL,
  `role_name` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `roles` (`role_id`, `role_name`) VALUES
(0, 'Admin'),
(1, 'Seller'),
(2, 'Buyer');


CREATE TABLE `user_tokens` (
  `user_token_id` int(11) NOT NULL,
  `user_id` varchar(36) DEFAULT NULL,
  `token_id` varchar(36) DEFAULT NULL,
  `issued_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expired_at` timestamp NOT NULL DEFAULT (current_timestamp() + interval 1 hour)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

