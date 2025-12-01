# Imar Group Admin Panel

A professional, responsive admin dashboard for Imar Group's investment management system. Built with pure HTML, CSS, and JavaScript—no dependencies required.

## Features

### 📊 Dashboard Overview
- Real-time statistics cards showing:
  - Total investments
  - Total clients
  - Active portfolios
  - Revenue metrics

### 📈 Data Visualization
- Line chart for investment growth trends
- Doughnut chart for portfolio distribution
- Interactive charts powered by Chart.js

### 📋 Investment Management
- Comprehensive data table with sorting
- Investment tracking with status indicators
- Quick actions (view, edit, delete)
- Modal forms for investment management

### 🔍 Search & Filter
- Real-time search across investments
- Date range filtering
- Status-based filtering support

### 👤 User Interface
- Responsive sidebar navigation
- Top navigation bar with user profile
- Search functionality
- Notification system
- Mobile-optimized design

## Project Structure

```
├── index.html          # Main HTML file with dashboard layout
├── css/
│   └── styles.css     # Complete styling with CSS variables
├── js/
│   └── script.js      # All dashboard functionality
├── assets/            # Images and icons directory
└── .github/
    └── copilot-instructions.md
```

## Quick Start

### Option 1: Open in Browser
Simply open `index.html` in any modern web browser:
```bash
# Windows
start index.html

# macOS
open index.html

# Linux
xdg-open index.html
```

### Option 2: Live Server
Use VS Code's Live Server extension:
1. Install "Live Server" extension
2. Right-click `index.html`
3. Select "Open with Live Server"

### Option 3: Using Python
```bash
# Python 3.x
python -m http.server 8000

# Python 2.x
python -m SimpleHTTPServer 8000
```
Then navigate to `http://localhost:8000`

## Technologies

- **HTML5** - Semantic markup
- **CSS3** - Modern styling with variables, flexbox, and grid
- **JavaScript (ES6+)** - Vanilla JS, no frameworks
- **Chart.js** - Data visualization library (CDN)
- **Font Awesome** - Icon library (CDN)

## Features in Detail

### Dashboard Sections

#### Statistics Cards
Shows key performance indicators with visual icons and trend information.

#### Investment Growth Chart
Line chart displaying monthly investment growth trends with interactive points.

#### Portfolio Distribution
Doughnut chart showing the breakdown of portfolio across different investment types.

#### Recent Investments Table
Data table listing recent investments with:
- Investment ID
- Client name
- Investment amount
- Investment type
- Status badge
- Date
- Action buttons

### Interactive Features

#### Search
Real-time filtering of table data as you type in the search box.

#### Date Range Filter
Select custom date ranges to filter data. Includes validation for date order.

#### Investment Actions
- **View**: See investment details (placeholder)
- **Edit**: Open modal form to edit investment information
- **Delete**: Confirm and delete investment with validation

#### Responsive Design
Fully responsive layout that works on:
- Desktop (1920px and above)
- Tablet (768px - 1024px)
- Mobile (320px - 767px)

## Customization

### Color Scheme
Edit CSS variables in `css/styles.css`:
```css
:root {
    --primary-color: #1e3a8a;
    --secondary-color: #3b82f6;
    --success-color: #10b981;
    /* ... more variables ... */
}
```

### Company Details
Update the following in `index.html`:
- Logo text: `<h1 class="logo">Your Company Name</h1>`
- Navigation items in sidebar
- Dashboard title and breadcrumbs

### Data Integration
In `js/script.js`, connect to your backend APIs:
- Replace mock data in chart functions
- Update table data fetching logic
- Implement real search and filter endpoints

## Browser Support

- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

## File Sizes

- `index.html` - ~15 KB
- `css/styles.css` - ~18 KB
- `js/script.js` - ~12 KB
- **Total**: ~45 KB (excluding CDN dependencies)

## Future Enhancements

- [ ] Backend API integration
- [ ] User authentication
- [ ] Data persistence
- [ ] Export to PDF/Excel
- [ ] Dark/Light theme toggle
- [ ] Real-time notifications
- [ ] Advanced filtering options
- [ ] Chart customization

## License

© 2024 Imar Group. All rights reserved.

## Support

For questions or issues, please contact the development team.

---

**Last Updated**: December 1, 2024
