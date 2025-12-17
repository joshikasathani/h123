# Hospital Booking System - Deployment Guide

## Quick Deployment Options

### 1. Vercel Deployment (Recommended)
**URL:** `https://h123.vercel.app`

**Steps:**
1. Go to [vercel.com](https://vercel.com)
2. Click "New Project"
3. Import GitHub repository: `joshikasathani/h123`
4. Click "Deploy"
5. Set environment variables:
   - `DB_HOST`: Your MySQL host
   - `DB_USER`: Your MySQL username
   - `DB_PASSWORD`: Your MySQL password
   - `DB_NAME`: Your database name
   - `TWILIO_ACCOUNT_SID`: Your Twilio SID
   - `TWILIO_AUTH_TOKEN`: Your Twilio token
   - `EMAIL_HOST`: Your SMTP host
   - `EMAIL_USER`: Your email username
   - `EMAIL_PASSWORD`: Your email password

### 2. Render Deployment (Alternative)
**URL:** `https://h123.onrender.com`

**Steps:**
1. Go to [render.com](https://render.com)
2. Click "New" → "Web Service"
3. Connect GitHub repository: `joshikasathani/h123`
4. Set runtime: PHP
5. Configure environment variables (same as Vercel)
6. Click "Create Web Service"

### 3. GitHub Pages (Frontend Only)
**URL:** `https://joshikasathani.github.io/h123/`

**Steps:**
1. Go to repository settings
2. Enable GitHub Pages
3. Select source: Deploy from branch
4. Choose main branch and root folder
5. Save (Note: PHP backend won't work)

## Environment Variables Required

### Database Configuration
```
DB_HOST=your_mysql_host
DB_USER=your_mysql_username
DB_PASSWORD=your_mysql_password
DB_NAME=your_database_name
```

### WhatsApp API (Twilio)
```
TWILIO_ACCOUNT_SID=your_twilio_sid
TWILIO_AUTH_TOKEN=your_twilio_token
```

### Email Configuration
```
EMAIL_HOST=your_smtp_host
EMAIL_USER=your_email_username
EMAIL_PASSWORD=your_email_password
```

## Database Setup

### MySQL Tables Required
```sql
-- Hospitals table
CREATE TABLE hospitals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hospital_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    address TEXT NOT NULL,
    specialization VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Appointments table
CREATE TABLE appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hospital_id INT NOT NULL,
    patient_name VARCHAR(255) NOT NULL,
    patient_phone VARCHAR(20) NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (hospital_id) REFERENCES hospitals(id)
);

-- Payments table
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    admin_share DECIMAL(10,2) NOT NULL,
    hospital_share DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id)
);
```

## Testing Deployment

### Test URLs
- **Home Page:** `https://h123.vercel.app/`
- **Dashboard:** `https://h123.vercel.app/dashboard.html`
- **Book Appointment:** `https://h123.vercel.app/book-appointment.html`
- **Register Hospital:** `https://h123.vercel.app/admin/register-hospital.html`

### Test Features
1. Hospital registration with email confirmation
2. Appointment booking with WhatsApp notifications
3. Payment processing with revenue splitting
4. Real-time dashboard statistics
5. Admin management panel

## Troubleshooting

### Common Issues
1. **Database Connection**: Ensure environment variables are correct
2. **WhatsApp API**: Verify Twilio credentials and phone numbers
3. **Email Sending**: Check SMTP configuration
4. **PHP Errors**: Check server logs for error details

### Support
- GitHub Repository: `https://github.com/joshikasathani/h123.git`
- Issues: Create GitHub issue for deployment problems

## Production Considerations

1. **Security**: Use environment variables for sensitive data
2. **Database**: Use production MySQL database
3. **Domain**: Configure custom domain if needed
4. **SSL**: Automatic SSL provided by Vercel/Render
5. **Monitoring**: Set up error tracking and monitoring

## Deployment Complete!

Your Hospital Booking System is now live and accessible to the public!
