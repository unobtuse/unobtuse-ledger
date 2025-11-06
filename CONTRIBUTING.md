# Contributing to Unobtuse Ledger

First off, thank you for considering contributing to Unobtuse Ledger! 🎉

It's people like you that make Unobtuse Ledger such a great tool for managing personal finances. We welcome contributions from everyone, whether you're fixing a typo, proposing a new feature, or submitting a major enhancement.

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [How Can I Contribute?](#how-can-i-contribute)
  - [Reporting Bugs](#reporting-bugs)
  - [Suggesting Features](#suggesting-features)
  - [Code Contributions](#code-contributions)
  - [Documentation](#documentation)
- [Development Setup](#development-setup)
- [Development Workflow](#development-workflow)
- [Coding Standards](#coding-standards)
- [Commit Message Guidelines](#commit-message-guidelines)
- [Pull Request Process](#pull-request-process)
- [Testing Requirements](#testing-requirements)
- [Security Vulnerabilities](#security-vulnerabilities)

---

## Code of Conduct

This project and everyone participating in it is governed by our Code of Conduct. By participating, you are expected to uphold this code. Please report unacceptable behavior to [conduct@unobtuse.com](mailto:conduct@unobtuse.com).

### Our Pledge

We pledge to make participation in our project a harassment-free experience for everyone, regardless of age, body size, disability, ethnicity, gender identity and expression, level of experience, nationality, personal appearance, race, religion, or sexual identity and orientation.

### Our Standards

**Positive behaviors include:**
- Using welcoming and inclusive language
- Being respectful of differing viewpoints and experiences
- Gracefully accepting constructive criticism
- Focusing on what is best for the community
- Showing empathy towards other community members

**Unacceptable behaviors include:**
- The use of sexualized language or imagery
- Trolling, insulting/derogatory comments, and personal or political attacks
- Public or private harassment
- Publishing others' private information without explicit permission
- Other conduct which could reasonably be considered inappropriate in a professional setting

---

## How Can I Contribute?

### Reporting Bugs

Before creating bug reports, please check the [existing issues](https://github.com/yourusername/unobtuse-ledger/issues) to see if the problem has already been reported. If it has **and the issue is still open**, add a comment to the existing issue instead of opening a new one.

#### How to Submit a Good Bug Report

Bugs are tracked as [GitHub issues](https://github.com/yourusername/unobtuse-ledger/issues). Create an issue and provide the following information:

- **Use a clear and descriptive title** for the issue
- **Describe the exact steps to reproduce the problem** in as much detail as possible
- **Provide specific examples** to demonstrate the steps
- **Describe the behavior you observed** after following the steps
- **Explain which behavior you expected to see instead** and why
- **Include screenshots or animated GIFs** if possible
- **Include your environment details:**
  - PHP version
  - Laravel version
  - Operating system
  - Browser (if frontend issue)
  - Database version

**Bug Report Template:**

```markdown
**Description:**
A clear and concise description of the bug.

**Steps to Reproduce:**
1. Go to '...'
2. Click on '...'
3. Scroll down to '...'
4. See error

**Expected Behavior:**
What you expected to happen.

**Actual Behavior:**
What actually happened.

**Screenshots:**
If applicable, add screenshots.

**Environment:**
- OS: [e.g., Ubuntu 22.04]
- PHP Version: [e.g., 8.3]
- Laravel Version: [e.g., 12.0]
- Browser: [e.g., Chrome 120]

**Additional Context:**
Any other context about the problem.
```

### Suggesting Features

Feature suggestions are tracked as [GitHub issues](https://github.com/yourusername/unobtuse-ledger/issues). Before creating enhancement suggestions, please check the issue list as you might find that you don't need to create one.

#### How to Submit a Good Feature Request

- **Use a clear and descriptive title** for the issue
- **Provide a step-by-step description** of the suggested enhancement
- **Provide specific examples** to demonstrate the feature
- **Describe the current behavior** and **explain which behavior you expected** instead
- **Explain why this enhancement would be useful** to most users
- **List any similar features** in other applications

**Feature Request Template:**

```markdown
**Is your feature request related to a problem?**
A clear description of the problem. Ex. I'm always frustrated when [...]

**Describe the solution you'd like:**
A clear and concise description of what you want to happen.

**Describe alternatives you've considered:**
A clear and concise description of any alternative solutions or features you've considered.

**Additional context:**
Any other context, mockups, or screenshots about the feature request.

**Potential implementation:**
If you have ideas on how to implement this, please share.
```

### Code Contributions

We love code contributions! Here's how to get started:

1. **Check existing issues** - Look for issues labeled `good first issue` or `help wanted`
2. **Discuss major changes** - For large changes, please open an issue first to discuss your approach
3. **Fork the repository** - Create your own fork to work in
4. **Create a branch** - Branch off from `develop` (not `main`)
5. **Make your changes** - Follow our coding standards
6. **Write tests** - Include tests for new functionality
7. **Submit a pull request** - Follow our PR template

### Documentation

Improvements to documentation are always welcome! This includes:

- Fixing typos or grammatical errors
- Adding examples or clarifications
- Writing tutorials or guides
- Improving API documentation
- Translating documentation

Documentation lives in:
- `/docs` - Project documentation
- `README.md` - Main project readme
- Code comments - Inline documentation
- API docs - Generated from docblocks

---

## Development Setup

### Prerequisites

- PHP 8.3 or higher
- Composer 2.x
- Node.js 18+ and NPM
- PostgreSQL (via Supabase or local)
- Redis (for cache and queues)
- Git

### Initial Setup

1. **Fork and clone the repository:**

```bash
git clone https://github.com/YOUR_USERNAME/unobtuse-ledger.git
cd unobtuse-ledger
```

2. **Install PHP dependencies:**

```bash
composer install
```

3. **Install JavaScript dependencies:**

```bash
npm install
```

4. **Copy environment file:**

```bash
cp .env.example .env
```

5. **Generate application key:**

```bash
php artisan key:generate
```

6. **Configure your `.env` file:**

```env
DB_CONNECTION=pgsql
DB_HOST=your-supabase-host
DB_PORT=5432
DB_DATABASE=your-database
DB_USERNAME=your-username
DB_PASSWORD=your-password

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Add other required credentials
```

7. **Run database migrations:**

```bash
php artisan migrate
```

8. **Seed the database (optional):**

```bash
php artisan db:seed
```

9. **Build frontend assets:**

```bash
npm run build
```

10. **Start the development server:**

```bash
composer dev
```

This will start:
- Laravel development server (http://localhost:8000)
- Queue worker
- Log viewer (Laravel Pail)
- Vite dev server (hot module replacement)

### Running Tests

```bash
# Run all tests
composer test

# Run specific test suite
php artisan test --testsuite=Feature

# Run tests with coverage
php artisan test --coverage

# Run code style checks
composer pint
```

---

## Development Workflow

### Branch Strategy

We follow **Git Flow** methodology:

- `main` - Production-ready code (protected)
- `develop` - Integration branch for features (default branch)
- `feature/*` - New features
- `bugfix/*` - Bug fixes
- `hotfix/*` - Urgent production fixes

### Creating a Feature Branch

```bash
# Update your local develop branch
git checkout develop
git pull origin develop

# Create a new feature branch
git checkout -b feature/your-feature-name

# Work on your changes
# ... make changes, commit frequently ...

# Push your branch
git push origin feature/your-feature-name
```

### Naming Conventions

**Branch Names:**
- `feature/add-receipt-scanning`
- `feature/improve-dashboard-performance`
- `bugfix/fix-transaction-sync`
- `hotfix/security-patch-auth`

**Good branch names:**
- ✅ `feature/subscription-detection`
- ✅ `bugfix/plaid-sync-error`
- ✅ `refactor/budget-service`

**Bad branch names:**
- ❌ `fix`
- ❌ `new-feature`
- ❌ `john-dev-branch`

---

## Coding Standards

### PHP Standards

We follow **PSR-12** coding standards for PHP code.

**Key principles:**
- Use strict typing: `declare(strict_types=1);`
- Type hint everything (parameters, return types, properties)
- Use PHP 8.3+ features when appropriate
- Follow SOLID principles
- Write descriptive variable and method names

**Example:**

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\Transaction;
use Illuminate\Support\Collection;

class BudgetService
{
    /**
     * Calculate the remaining budget for a user.
     *
     * @param User $user The user to calculate budget for
     * @param string $month The month in Y-m format (e.g., "2025-01")
     * @return float The remaining budget amount
     */
    public function calculateRemaining(User $user, string $month): float
    {
        $totalIncome = $this->getTotalIncome($user, $month);
        $totalExpenses = $this->getTotalExpenses($user, $month);
        
        return $totalIncome - $totalExpenses;
    }
    
    private function getTotalIncome(User $user, string $month): float
    {
        // Implementation
    }
}
```

**Code Style Enforcement:**

```bash
# Check code style
composer pint

# Automatically fix code style issues
composer pint --fix
```

### Laravel Best Practices

- **Use Eloquent ORM** instead of raw SQL queries
- **Use Form Requests** for validation
- **Use Service Classes** for business logic
- **Use Resource Classes** for API responses
- **Use Jobs** for long-running tasks
- **Use Events** for decoupled code
- **Avoid fat controllers** - keep controllers thin

### Frontend Standards

- **Use Livewire** for reactive components
- **Use Alpine.js** for lightweight interactions
- **Follow Tailwind CSS** utility-first approach
- **Use Blade components** for reusable UI elements
- **Avoid inline JavaScript** in Blade templates

### Database Standards

- **Always create migrations** for schema changes
- **Use descriptive migration names**
- **Add indexes** for frequently queried columns
- **Use foreign key constraints**
- **Use UUIDs** for primary keys (already configured)

**Good migration names:**
- ✅ `create_transactions_table`
- ✅ `add_category_to_transactions_table`
- ✅ `create_index_on_transactions_date`

### Documentation Standards

All public methods must have docblocks:

```php
/**
 * Sync transactions from Plaid for a given account.
 *
 * This method fetches new transactions from Plaid API and stores
 * them in the database. It handles pagination and rate limiting.
 *
 * @param Account $account The account to sync transactions for
 * @param int $days Number of days to sync (default: 30)
 * @return Collection<Transaction> Collection of synced transactions
 * @throws PlaidException When Plaid API returns an error
 */
public function syncTransactions(Account $account, int $days = 30): Collection
{
    // Implementation
}
```

---

## Commit Message Guidelines

We follow **Conventional Commits** specification.

### Format

```
<type>(<scope>): <subject>

<body>

<footer>
```

### Types

- `feat` - A new feature
- `fix` - A bug fix
- `docs` - Documentation only changes
- `style` - Code style changes (formatting, missing semi-colons, etc)
- `refactor` - Code change that neither fixes a bug nor adds a feature
- `perf` - Performance improvements
- `test` - Adding or updating tests
- `chore` - Changes to build process or auxiliary tools
- `ci` - Changes to CI configuration files and scripts

### Examples

**Good commit messages:**

```
feat(auth): implement Google OAuth integration

- Add Socialite configuration
- Create OAuth controller and routes
- Implement callback handling
- Add user creation from OAuth data

Closes #123
```

```
fix(plaid): resolve transaction sync timeout issue

Transaction sync was timing out for accounts with >1000 transactions.
Implemented chunking to process transactions in batches of 100.

Fixes #456
```

```
docs(readme): update installation instructions

Added steps for Redis configuration and Plaid API setup.
```

**Bad commit messages:**

- ❌ `fixed bug`
- ❌ `updated files`
- ❌ `wip`
- ❌ `changes`

### Commit Frequency

- **Commit often** - After each logical unit of work
- **Don't commit broken code** - Code should compile/run
- **One concern per commit** - Don't mix unrelated changes

---

## Pull Request Process

### Before Submitting

1. **Update your branch** with latest `develop`:
   ```bash
   git checkout develop
   git pull origin develop
   git checkout your-branch
   git merge develop
   ```

2. **Run all tests** and ensure they pass:
   ```bash
   composer test
   ```

3. **Check code style**:
   ```bash
   composer pint
   ```

4. **Update documentation** if needed

5. **Add or update tests** for your changes

### Pull Request Template

When you create a PR, please include:

```markdown
## Description
Brief description of what this PR does.

## Type of Change
- [ ] Bug fix (non-breaking change which fixes an issue)
- [ ] New feature (non-breaking change which adds functionality)
- [ ] Breaking change (fix or feature that would cause existing functionality to not work as expected)
- [ ] Documentation update

## Related Issue
Closes #123

## Changes Made
- Change 1
- Change 2
- Change 3

## Testing
- [ ] Unit tests added/updated
- [ ] Integration tests added/updated
- [ ] Manual testing performed

### Test Coverage
- Current coverage: XX%
- Coverage after changes: XX%

## Screenshots (if applicable)
Add screenshots for UI changes.

## Checklist
- [ ] My code follows the project's coding standards
- [ ] I have performed a self-review of my code
- [ ] I have commented my code, particularly in hard-to-understand areas
- [ ] I have made corresponding changes to the documentation
- [ ] My changes generate no new warnings or errors
- [ ] I have added tests that prove my fix is effective or that my feature works
- [ ] New and existing unit tests pass locally with my changes
- [ ] Any dependent changes have been merged and published

## Additional Notes
Any additional context or notes for reviewers.
```

### Review Process

1. **Automated checks** run (tests, linting)
2. **Code review** by 1-2 team members
3. **Address feedback** - Make requested changes
4. **Re-review** if significant changes made
5. **Approval** - PR is approved
6. **Merge** - PR is merged to `develop`

### After Your PR is Merged

1. **Delete your feature branch**:
   ```bash
   git branch -d feature/your-feature-name
   git push origin --delete feature/your-feature-name
   ```

2. **Update your local develop**:
   ```bash
   git checkout develop
   git pull origin develop
   ```

---

## Testing Requirements

All code contributions should include appropriate tests.

### Test Coverage Requirements

- **Minimum 80% coverage** for new code
- **All new features** must have tests
- **All bug fixes** must have a test that would have caught the bug

### Types of Tests

**Unit Tests:**
- Test individual methods/functions in isolation
- Mock external dependencies
- Fast execution

**Feature Tests:**
- Test complete features end-to-end
- Test HTTP endpoints
- Test database interactions

**Integration Tests:**
- Test interaction with external services (Plaid, AI)
- Use mocks/stubs for external APIs

### Writing Good Tests

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BudgetCalculationTest extends TestCase
{
    use RefreshDatabase;

    public function test_calculates_remaining_budget_correctly(): void
    {
        // Arrange
        $user = User::factory()->create();
        $income = 5000;
        $expenses = 3000;
        
        // Act
        $remaining = $user->calculateRemainingBudget();
        
        // Assert
        $this->assertEquals(2000, $remaining);
    }
}
```

**Good test characteristics:**
- ✅ Clear test name describing what is tested
- ✅ Follows Arrange-Act-Assert pattern
- ✅ Tests one thing
- ✅ Independent of other tests
- ✅ Fast execution

---

## Security Vulnerabilities

If you discover a security vulnerability, please **DO NOT** open a public issue.

Instead, email us at **security@unobtuse.com** with:
- Description of the vulnerability
- Steps to reproduce
- Potential impact
- Suggested fix (if you have one)

We will respond within 48 hours and work with you to address the issue.

### Security Best Practices

When contributing code:
- **Never commit** sensitive data (API keys, passwords, tokens)
- **Always validate** user input
- **Use parameterized queries** (Eloquent does this automatically)
- **Sanitize output** to prevent XSS
- **Follow OWASP** security guidelines
- **Keep dependencies updated**

---

## Questions?

If you have questions about contributing, feel free to:

- 💬 **Join our Discord:** [discord.gg/unobtuse](https://discord.gg/unobtuse)
- 📧 **Email us:** dev@unobtuse.com
- 🐦 **Tweet at us:** [@UnobtuseLedger](https://twitter.com/unobtuseLedger)
- 📖 **Check the docs:** [docs.unobtuse.com](https://docs.unobtuse.com)

---

## Recognition

Contributors will be recognized in:
- Our `CONTRIBUTORS.md` file
- Release notes
- Project README
- Annual contributor spotlight posts

---

## License

By contributing to Unobtuse Ledger, you agree that your contributions will be licensed under the same [MIT License](LICENSE) that covers the project.

---

## Thank You! 🙏

Your contributions help make financial management accessible to everyone. We appreciate your time and effort!

<div align="center">

**Happy Coding!** 🚀

![GabeMade.it](logos/gabemadeit-white-logo.svg)

</div>


