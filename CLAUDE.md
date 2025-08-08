# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

SROIV4 is a Social Return on Investment (SROI) evaluation system built in PHP with MySQL. It helps organizations measure and analyze social impact of projects through a structured pathway from strategies to outcomes and financial impact ratios.

## Development Environment

This is a XAMPP/local PHP development project:
- **Server**: XAMPP (Apache + MySQL + PHP)  
- **Database**: MariaDB/MySQL (database name: `sroiv4`)
- **Frontend**: Bootstrap 5, vanilla JavaScript with Thai language support
- **Architecture**: Traditional PHP with session-based authentication

## Database Management

### Database Setup
```bash
# Import main database structure
mysql -u root -p sroiv4 < sroiv4.sql

# Apply updates if needed  
mysql -u root -p sroiv4 < database-update.sql
mysql -u root -p sroiv4 < benefit_note_migration.sql
```

### Connection Configuration
Database connection is configured in `config.php`:
- Host: localhost
- Username: root  
- Password: (empty)
- Database: sroiv4

## Core System Architecture

### Impact Chain Workflow
The system follows a 4-step SROI methodology:

1. **Step 1 - Strategy Selection** (`impact-chain/step1-strategy.php`)
   - Select organizational strategies
   - Processed by `impact-chain/process-step1.php`

2. **Step 2 - Activity Definition** (`impact-chain/step2-activity.php`) 
   - Define activities under selected strategies
   - Processed by `impact-chain/process-step2.php`

3. **Step 3 - Output Mapping** (`impact-chain/step3-output.php`)
   - Map outputs from activities 
   - Processed by `impact-chain/process-step3.php`

4. **Step 4 - Outcome Analysis** (`impact-chain/step4-outcome.php`)
   - Define outcomes and impact ratios
   - Processed by `impact-chain/process-step4.php`

### Impact Pathway Module
Located in `impact_pathway/` directory:
- **Base Case Analysis** (`basecase.php`) - Financial baseline calculations
- **With-Without Analysis** (`with-without.php`) - Comparative impact analysis  
- **Cost-Benefit Analysis** (`cost.php`, `benefit.php`) - Financial calculations
- **Final Calculation** (`impact_pathway.php`) - SROI ratio computation

### Key Database Tables

**Core Project Tables:**
- `projects` - Main project information
- `users` - User management with role-based access
- `project_strategies`, `project_activities`, `project_outputs` - Impact chain relationships

**Reference Data Tables:**
- `strategies` - Predefined organizational strategies
- `activities` - Standard activities linked to strategies  
- `outputs` - Expected outputs from activities
- `outcomes` - Measurable social outcomes

**Financial Analysis Tables:**
- `project_impact_ratios` - Store calculated SROI ratios
- `proxy_data` - Financial proxy values for impact measurement

## Common Development Tasks

### Starting Development
```bash
# Start XAMPP services
sudo /Applications/XAMPP/xamppfiles/xampp start

# Access application
# http://localhost/sroiv4/
```

### Testing Database Connections
```bash
# Test database connectivity
php test_db.php

# Test session handling
php test_session.php

# System integration test
php test-system.php
```

### Project Creation Flow
1. User creates project via `create-project.php`
2. Project listed in `project-list.php` and `dashboard.php`
3. Impact chain creation starts from `impact-chain/step1-strategy.php`
4. Final analysis performed in `impact_pathway/` modules

### Debugging Tools
- `debug-step3-complete.php` - Step 3 debugging
- `debug-step4.php` - Step 4 debugging  
- `check_project_data.php` - Project data verification
- `check-table-structure.php` - Database structure validation

## Authentication & Sessions

- Session management via `session_helper.php`
- Login/logout functionality in `login.php`/`logout.php`
- User registration in `register.php`
- Role-based access control throughout system

## File Organization

**Core Application:**
- Root level: Main pages (dashboard, project management)
- `impact-chain/`: Step-by-step SROI workflow
- `impact_pathway/`: Financial calculations and analysis
- `includes/`: Shared utilities and helpers

**Database:**
- `*.sql` files: Database schema and updates
- SQL files follow incremental update pattern

## Development Notes

- All text content is in Thai language
- Bootstrap 5 used for responsive UI
- Extensive use of prepared statements for database security
- Session-based authentication with user role management
- Real-time dashboard with project statistics
- Audit logging system tracks all data changes

## Testing

Run system integration tests:
```bash
php test-system.php        # Overall system test
php test_session.php       # Session functionality  
php test_db.php           # Database connectivity
```

For Impact Chain testing:
```bash
php test-step3-integration.php  # Step 3 integration test
```