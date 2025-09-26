# Awyiss CMS

![Awyiss CMS](awyiss/assets/img/logo-awyiss.png)

![Version](https://img.shields.io/badge/Version-0.1.0%20%22Interface%22-63D1A5?style=for-the-badge&labelColor=131A21)\
![PHP](https://img.shields.io/badge/PHP-8.4+-63D1A5?style=for-the-badge&labelColor=131A21)\
![License](https://img.shields.io/badge/License-MIT-63D1A5?style=for-the-badge&labelColor=131A21)

## Overview

**Awyiss CMS** is a powerful, flexible content management system built on top of the CakePHP framework.

It provides a robust foundation for creating modern, responsive websites, with a focus on ease of use and customizability.

It is designed to do the "heavy lifting" when building new websites, allowing developers to focus on delivering unique features and functionality for their clients
without the need to reinvent the wheel for common CMS tasks.

## Features

- **Advanced Content Management**:\
Intuitive interface for managing pages and contents
with a focus on flexibility and reusability
- **Modules-Architecture**:\
Easily extendable with custom modules
- **User Management**:\
Comprehensive user and permission management
- **Multilingual Support**:\
Built-in support for multiple languages in both Backend and Frontend
- **SEO Tools**:\
Tools to analyze optimize your content for search engines
- **Media Management**:\
Powerful media library with image manipulation
- **Form Builder**:\
Create and manage custom forms and protect them with predefined or custom protection mechanisms
- **Email Templates**:\
Design and customize email templates
- **Data Tables**:\
Flexible management of data that doesn't fit into other categories
- **Attributes System**:\
Extensible attribute management that allows customization, depending on your needs
- **Event System**:\
Hook into core functionality through events
- **Responsive Design**:\
Mobile-friendly backend interface and a solid foundation for building responsive frontends

## Requirements

- PHP 8.4 or higher
- MySQL 5.7+ or MariaDB 10.3+
- Composer
- Web server (Apache or Nginx) with URL rewriting enabled and rights to create symlinks
- Shell access with PHP CLI
- Cronjob support (often & long-running)
- Imagick or GD PHP extension (for image manipulation)
- FileInfo PHP extension
- Intl PHP extension

## Installation

> [!TIP]
> It's recommended to run the command as the web server user to ensure proper file permissions,
> for example using `sudo runuser -u <username> -- <command>` on Debian-based systems.

![Awyiss Installation](awyiss/assets/img/installation-process.svg)

### 1. Clone the Repository

```bash
git clone https://github.com/fabian-mcfly/Awyiss.git
cd Awyiss
```

### 2. Make the cake console executable

```bash
chmod +x bin/cake
```

### 3. Install Dependencies

```bash
composer install
```

### 4. Run the Installer and follow the prompts

```bash
bin/cake awyiss install
```

### 5. Configure your Web Server

Set up your web server to point to the `webroot` directory and create the cronjobs.

Example Cronjob entries:

```bash
*/10 * * * * cd /var/www/ && bin/cake queue run -q -g general >> /var/www/logs/cron.log 2>&1
*/1 * * * * cd /var/www/ && bin/cake media convert_files --include-avif --include-webp -q
```

> [!TIP]
> You can often access the cronjob configuration with `crontab -e`, or for a specific user using `(sudo) crontab -e -u www-data`.

### 6. Access the Admin Interface

You can now access the admin interface at `http://your-domain.com/backend` and log in with the credentials you provided during installation.

### 7. Optional Steps

- Execute the `detect_available_commands` command
    ```bash
    bin/cake media detect_available_commands
    ```
    This will check if the cli tools are available (`ffmpg`, ImageMagicks `convert`, `mogrify`) and if certain formats are supported (like `avif`, `webp`, `pdf` and `docx`).
- Create a backup of the initial installation
    ```bash
    bin/cake awyiss backup
    ```

> [!TIP]
> Awyiss creates multiple symlinks in the `webroot` folder to link assets from the core and your customer folder to eliminate the need for building steps.
> - `/webroot/assets/` -> `/<customer name>/assets/`
> - `/webroot/awyiss/assets/` -> `/awyiss/assets/`
>
> To rebuild them, in case you delete them or move the installation, run:
> ```bash
> bin/cake awyiss install --rebuild-symlinks
> ```

## Documentation

More detailed documentation is available in the [official documentation](https://docs.awyiss.2fmedia).

## License

**Awyiss** is licensed under the MIT License.\
See the [LICENSE](LICENSE) file for details.

## Support

For support inquiries, please contact [awyiss@2f.media](mailto:awyiss@2f.media).

---

© 2025 Awyiss CMS. All rights reserved.
