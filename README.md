# FacilityOps System

## Overview
FacilityOps is a simple booking system designed for staff to reserve facilities such as meeting rooms or discussion rooms. It allows staff to view available facilities, make bookings, and manage their reservations efficiently. This system makes the booking process faster, more organized, and helps avoid conflicts or double bookings.

## Features
- Staff login and authentication
- View available facilities
- Make, update, and cancel bookings
- Check booking history
- Conflict-free scheduling (prevents double bookings)

## Technologies Used
- **Backend:** PHP
- **Frontend:** HTML, CSS, JavaScript
- **Database:** MySQL
- **Local Server:** XAMPP / WAMP

## Project Structure
```
facilityops/
├── facilityops/          # Main application source code
├── booking1.png          # Screenshot - booking page
├── booking3.png          # Screenshot - booking page
├── login.png             # Screenshot - login page
├── README.md
└── ...
```

## Installation

1. **Clone this repository**
   ```bash
   git clone https://github.com/nvrnsya/facilityops.git
   ```

2. **Move the project folder into your server's root directory**
   - XAMPP: `C:/xampp/htdocs/facilityops`
   - WAMP: `C:/wamp64/www/facilityops`

3. **Start Apache and MySQL**
   - Open XAMPP/WAMP Control Panel
   - Start the **Apache** and **MySQL** modules

4. **Create the database**
   - Open `phpMyAdmin` (`http://localhost/phpmyadmin`)
   - Create a new database (e.g. `facilityops_db`)
   - Import the provided `.sql` file (if available) under the **Import** tab

5. **Configure database connection**
   - Open the database config file (e.g. `config.php` / `db_connect.php`)
   - Update the database name, username, and password to match your local setup

6. **Run the application**
   - Open your browser and go to:
     ```
     http://localhost/facilityops
     ```

## Usage
1. Login with staff credentials.
2. View available facilities.
3. Select date, time, and facility to make a booking.
4. View or cancel existing bookings.

## Screenshots

### Login Page
![Login Page](login.png)

### Booking Page
![Booking Page 1](booking1.png)
![Booking Page 2](booking3.png)

## System Design
```
docs/
├── erd.png
└── flowchart.png
```

## Roadmap / Future Improvements
- [ ] Email notification for booking confirmation
- [ ] Admin dashboard for managing all bookings
- [ ] Export booking history to PDF/Excel
- [ ] Role-based access (Staff vs Admin)


## Author
Developed by **nureen, muaz, dinie** as a Final Year Project (FYP).

## License
This project is for academic purposes (Final Year Project). Feel free to use this as a reference.
