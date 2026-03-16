# App Assets Directory

This directory contains CSS and JavaScript assets used by the admin and patient portal pages.

## Structure

```
app/assets/
├── css/
│   ├── dark-mode.css           # Dark mode styles
│   └── responsive-sidebar.css  # Sidebar responsive styles
└── js/
    ├── dark-mode.js            # Dark mode toggle functionality
    ├── feedback-form.js        # Feedback form handling
    ├── init.js                 # Initialization scripts
    ├── mobile-menu.js          # Mobile menu functionality
    └── sidebar-toggle.js       # Sidebar toggle functionality
```

## Note

These files are copies from `public/assets/` for easier path referencing from the admin and patient PHP pages. If you update assets in `public/assets/`, make sure to sync them here as well.

## Syncing Assets

To sync assets from public to app directory, run:

```powershell
Copy-Item "public\assets\css\*.css" -Destination "app\assets\css\" -Force
Copy-Item "public\assets\js\*.js" -Destination "app\assets\js\" -Force
```
