/**
 * Sample Data for Movie Booking System
 * 
 * Static reference data used for UI demonstration and fallback content.
 * Note: This file is NOT connected to the database - actual data comes from PHP APIs.
 * 
 * This file provides:
 * - Sample movie data organized by genre
 * - Sample showtime slots
 * - Sample theater names
 * - Sample booking records (for admin UI demo)
 * - Sample user records (for admin UI demo)
 * 
 * @note: These are placeholder samples for frontend UI demonstration.
 *        The actual movie/showtime/booking data is fetched from:
 *        - movies table via admin-movies.php
 *        - showtimes table via get-showtimes.php
 *        - bookings table via admin-bookings.php
 *        - users table via login/registration
 * 
 * @used-by: admin-header.php:27 (script include for admin pages)
 * @see get-showtimes.php (real showtimes API)
 * @see get-seats-fixed.php (real seats API)
 * @see admin-movies.php (real movies CRUD)
 * @see admin-bookings.php (real bookings management)
 */

// Sample movies data - organized by genre for category display
// These are hardcoded demo values; actual movies come from database
const movies = {
  // Action genre movies
  action: [
    {
      id: 1,
      title: "Avengers: Endgame",
      description: "The Avengers assemble once more to reverse Thanos' actions.",
      poster: "https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=400&h=600&fit=crop",
      genre: "Action",
      duration: "181 min"
    },
    {
      id: 2,
      title: "John Wick 4",
      description: "John Wick uncovers a path to defeat the High Table.",
      poster: "https://images.unsplash.com/photo-1607853202273-b19e91751c9f?w=400&h=600&fit=crop",
      genre: "Action",
      duration: "169 min"
    },
    {
      id: 3,
      title: "Mission Impossible: Dead Reckoning",
      description: "Ethan Hunt races against time to stop a rogue AI.",
      poster: "https://images.unsplash.com/photo-1593642702821-c8da6771f0c6?w=400&h=600&fit=crop",
      genre: "Action",
      duration: "163 min"
    }
  ],
  // Comedy genre movies
  comedy: [
    {
      id: 4,
      title: "Barbie",
      description: "Barbie leaves the ideal world of Barbieland to find true happiness.",
      poster: "https://images.unsplash.com/photo-1685706559456-f8e2a1abff6e?w=400&h=600&fit=crop",
      genre: "Comedy",
      duration: "114 min"
    },
    {
      id: 5,
      title: "Super Mario Bros Movie",
      description: "Mario embarks on a mission to rescue Luigi from Bowser.",
      poster: "https://images.unsplash.com/photo-1682685796186-6e87c5e6841f?w=400&h=600&fit=crop",
      genre: "Comedy",
      duration: "92 min"
    }
  ],
  // Drama genre movies
  drama: [
    {
      id: 6,
      title: "Oppenheimer",
      description: "The story of American scientist J. Robert Oppenheimer.",
      poster: "https://images.unsplash.com/photo-1682685795528-e492e678c0ed?w=400&h=600&fit=crop",
      genre: "Drama",
      duration: "180 min"
    },
    {
      id: 7,
      title: "Killers of the Flower Moon",
      description: "An oil boom brings unexpected wealth to the Osage Nation.",
      poster: "https://images.unsplash.com/photo-1682686578113-e8e209969f5f?w=400&h=600&fit=crop",
      genre: "Drama",
      duration: "206 min"
    }
  ]
};

// Sample showtime slots - template for available viewing times
// In production, showtimes come from showtimes table via get-showtimes.php
const showtimes = ['10:00 AM', '1:00 PM', '4:00 PM', '7:00 PM', '10:00 PM'];

// Sample theater names - corresponds to theaters in showtimes table
const theaters = ['Screen 1', 'Screen 2', 'IMAX', 'VIP'];

// Sample bookings - demo data for admin booking management table
// In production, bookings come from bookings table via admin-bookings.php
const bookings = [
  { id: 1, user: 'John Doe', movie: 'Avengers: Endgame', date: '2024-01-15', time: '7:00 PM', theater: 'Screen 1', status: 'Confirmed' },
  { id: 2, user: 'Jane Smith', movie: 'Barbie', date: '2024-01-16', time: '4:00 PM', theater: 'Screen 2', status: 'Confirmed' },
  { id: 3, user: 'Bob Johnson', movie: 'Oppenheimer', date: '2024-01-17', time: '10:00 PM', theater: 'IMAX', status: 'Pending' }
];

// Sample users - demo data for admin user management (if implemented)
// In production, users come from users table via database
const users = [
  { id: 1, name: 'John Doe', email: 'john@example.com' },
  { id: 2, name: 'Jane Smith', email: 'jane@example.com' },
  { id: 3, name: 'Bob Johnson', email: 'bob@example.com' }
];

