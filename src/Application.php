<?php

declare(strict_types=1);

namespace App;

use App\Services\MatchEngine;
use PDO;
use PDOException;
use Throwable;

final class Application
{
    public function dispatch(string $method, string $path): void
    {
        if ($method === 'GET' && $path === '/') {
            $items = $this->recentItems(6);
            render('home', compact('items'));
            return;
        }

        if ($method === 'GET' && $path === '/register') {
            render('auth/register', ['departments' => ['COM', 'SWE', 'IFT', 'CBS']]);
            return;
        }
        if ($method === 'POST' && $path === '/register') {
            $this->register();
            return;
        }
        if ($method === 'GET' && $path === '/login') {
            render('auth/login');
            return;
        }
        if ($method === 'POST' && $path === '/login') {
            $this->login();
            return;
        }
        if ($method === 'POST' && $path === '/logout') {
            verify_csrf();
            $_SESSION = [];
            session_regenerate_id(true);
            flash('You have signed out.');
            redirect('/');
        }

        if ($method === 'GET' && $path === '/dashboard') {
            $this->showDashboard();
            return;
        }
        if ($method === 'GET' && $path === '/items') {
            $this->browseItems();
            return;
        }
        if ($method === 'GET' && $path === '/items/new') {
            require_auth();
            $this->renderItemForm();
            return;
        }
        if ($method === 'POST' && $path === '/items/new') {
            $this->createItem();
            return;
        }
        if (preg_match('#^/items/([1-9][0-9]*)$#', $path, $matches) === 1 && $method === 'GET') {
            $this->showItem((int) $matches[1]);
            return;
        }
        if (preg_match('#^/items/([1-9][0-9]*)/claims$#', $path, $matches) === 1 && $method === 'POST') {
            $this->createClaim((int) $matches[1]);
            return;
        }
        if (preg_match('#^/items/([1-9][0-9]*)/flags$#', $path, $matches) === 1 && $method === 'POST') {
            $this->flagItem((int) $matches[1]);
            return;
        }

        if ($method === 'GET' && $path === '/claims') {
            $this->showClaims();
            return;
        }
        if (preg_match('#^/claims/([1-9][0-9]*)/review$#', $path, $matches) === 1 && $method === 'POST') {
            $this->reviewClaim((int) $matches[1]);
            return;
        }
        if (preg_match('#^/claims/([1-9][0-9]*)/complete$#', $path, $matches) === 1 && $method === 'POST') {
            $this->completeClaim((int) $matches[1]);
            return;
        }

        if ($method === 'GET' && $path === '/admin') {
            $this->showModeration();
            return;
        }
        if (preg_match('#^/admin/items/([1-9][0-9]*)$#', $path, $matches) === 1 && $method === 'POST') {
            $this->moderateItem((int) $matches[1]);
            return;
        }

        http_response_code(404);
        render('errors/not_found');
    }

    private function register(): void
    {
        verify_csrf();
        $matric = strtoupper(post_string('matric_number'));
        $name = post_string('full_name');
        $email = strtolower(post_string('email'));
        $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
        $confirmation = is_string($_POST['password_confirmation'] ?? null) ? $_POST['password_confirmation'] : '';

        if (preg_match('#^CST/\d{2}/(COM|SWE|IFT|CBS)/\d{5}$#', $matric, $match) !== 1) {
            flash('Enter a valid registration number, for example CST/24/COM/12345.', 'danger');
            redirect('/register');
        }
        if (
            mb_strlen($name) < 2 || mb_strlen($name) > 120
            || mb_strlen($email) > 254 || filter_var($email, FILTER_VALIDATE_EMAIL) === false
        ) {
            flash('Enter your full name and a valid email address.', 'danger');
            redirect('/register');
        }
        if (strlen($password) < 10 || strlen($password) > 200 || $password !== $confirmation) {
            flash('Passwords must match and contain at least 10 characters.', 'danger');
            redirect('/register');
        }

        try {
            $statement = db()->prepare(
                'INSERT INTO users (matric_number, department, full_name, email, password_hash)
                 VALUES (:matric, :department, :name, :email, :password_hash)'
            );
            $statement->execute([
                'matric' => $matric,
                'department' => $match[1],
                'name' => $name,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ]);
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                flash('That registration number or email is already registered.', 'danger');
                redirect('/register');
            }
            throw $exception;
        }

        session_regenerate_id(true);
        $_SESSION['user'] = $this->loadUser((int) db()->lastInsertId());
        flash('Your account is ready. You can now report or claim items.');
        redirect('/');
    }

    private function login(): void
    {
        verify_csrf();
        $identifier = strtoupper(post_string('identifier'));
        $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';

        $statement = db()->prepare('SELECT * FROM users WHERE matric_number = :matric OR email = :email LIMIT 1');
        $statement->execute(['matric' => $identifier, 'email' => strtolower($identifier)]);
        $user = $statement->fetch();
        $now = new \DateTimeImmutable('now');

        if ($user === false || ($user['locked_until'] !== null && new \DateTimeImmutable($user['locked_until']) > $now)) {
            flash('The sign-in details are invalid or the account is temporarily locked.', 'danger');
            redirect('/login');
        }

        if (!password_verify($password, $user['password_hash'])) {
            $attempts = (int) $user['failed_login_attempts'] + 1;
            $lockedUntil = $attempts >= 5 ? $now->modify('+15 minutes')->format('Y-m-d H:i:s') : null;
            $update = db()->prepare(
                'UPDATE users SET failed_login_attempts = :attempts, locked_until = :locked_until WHERE id = :id'
            );
            $update->execute([
                'attempts' => min($attempts, 255),
                'locked_until' => $lockedUntil,
                'id' => $user['id'],
            ]);
            flash('The sign-in details are invalid or the account is temporarily locked.', 'danger');
            redirect('/login');
        }

        $update = db()->prepare('UPDATE users SET failed_login_attempts = 0, locked_until = NULL WHERE id = :id');
        $update->execute(['id' => $user['id']]);
        session_regenerate_id(true);
        $_SESSION['user'] = $this->loadUser((int) $user['id']);
        flash('Welcome back, ' . $_SESSION['user']['full_name'] . '.');
        redirect('/');
    }

    private function browseItems(): void
    {
        $type = is_string($_GET['type'] ?? null) ? strtoupper(trim($_GET['type'])) : '';
        $query = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
        $query = mb_substr($query, 0, 100);
        $where = ["i.status IN ('ACTIVE', 'CLAIM_PENDING')"];
        $parameters = [];
        if (in_array($type, ['LOST', 'FOUND'], true)) {
            $where[] = 'i.item_type = :type';
            $parameters['type'] = $type;
        }
        if ($query !== '') {
            $where[] = '(i.title LIKE :title_query OR i.description LIKE :description_query OR c.name LIKE :category_query OR v.name LIKE :venue_query)';
            $search = '%' . $query . '%';
            $parameters['title_query'] = $search;
            $parameters['description_query'] = $search;
            $parameters['category_query'] = $search;
            $parameters['venue_query'] = $search;
        }
        $statement = db()->prepare($this->itemSelect() . ' WHERE ' . implode(' AND ', $where) . ' ORDER BY i.created_at DESC LIMIT 100');
        $statement->execute($parameters);
        $items = $statement->fetchAll();
        render('items/index', compact('items', 'query', 'type'));
    }

    private function showDashboard(): void
    {
        $user = require_auth();
        $statement = db()->prepare(
            $this->itemSelect() . ' WHERE i.reporter_id = :reporter ORDER BY i.updated_at DESC LIMIT 100'
        );
        $statement->execute(['reporter' => $user['id']]);
        $items = $statement->fetchAll();

        $statement = db()->prepare(
            'SELECT status, COUNT(*) AS total FROM claims WHERE claimant_id = :claimant GROUP BY status'
        );
        $statement->execute(['claimant' => $user['id']]);
        $claimCounts = array_column($statement->fetchAll(), 'total', 'status');
        render('dashboard/index', compact('items', 'claimCounts'));
    }

    private function renderItemForm(): void
    {
        $categories = db()->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
        $venues = db()->query('SELECT id, name, building FROM venues ORDER BY building, name')->fetchAll();
        render('items/form', compact('categories', 'venues'));
    }

    private function createItem(): void
    {
        $user = require_auth();
        verify_csrf();
        $type = strtoupper(post_string('item_type'));
        $title = post_string('title');
        $description = post_string('description');
        $eventDate = post_string('event_date');
        $categoryId = filter_var($_POST['category_id'] ?? null, FILTER_VALIDATE_INT);
        $venueId = filter_var($_POST['venue_id'] ?? null, FILTER_VALIDATE_INT);
        $question = post_string('challenge_question');
        $answer = post_string('challenge_answer');

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $eventDate);
        $dateErrors = \DateTimeImmutable::getLastErrors();
        if (
            !in_array($type, ['LOST', 'FOUND'], true)
            || mb_strlen($title) < 3 || mb_strlen($title) > 120
            || mb_strlen($description) < 10 || mb_strlen($description) > 5000
            || $categoryId === false || $venueId === false
            || $categoryId < 1 || $venueId < 1
            || $date === false || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))
            || $date > new \DateTimeImmutable('today')
        ) {
            flash('Check the item title, description, category, venue, type and date.', 'danger');
            redirect('/items/new');
        }
        $lookup = db()->prepare(
            'SELECT
                EXISTS(SELECT 1 FROM categories WHERE id = :category_id) AS category_exists,
                EXISTS(SELECT 1 FROM venues WHERE id = :venue_id) AS venue_exists'
        );
        $lookup->execute(['category_id' => $categoryId, 'venue_id' => $venueId]);
        $validLookups = $lookup->fetch();
        if ($validLookups === false || !(bool) $validLookups['category_exists'] || !(bool) $validLookups['venue_exists']) {
            flash('Choose a valid category and venue.', 'danger');
            redirect('/items/new');
        }
        if ($type === 'FOUND' && (mb_strlen($question) < 5 || mb_strlen($question) > 255 || mb_strlen($answer) < 1 || mb_strlen($answer) > 255)) {
            flash('Found items require a hidden-feature question and its answer.', 'danger');
            redirect('/items/new');
        }

        $statement = db()->prepare(
            'INSERT INTO items
                (reporter_id, category_id, venue_id, item_type, title, description, event_date, challenge_question, challenge_answer_hash)
             VALUES
                (:reporter, :category, :venue, :type, :title, :description, :event_date, :question, :answer_hash)'
        );
        $statement->execute([
            'reporter' => $user['id'],
            'category' => $categoryId,
            'venue' => $venueId,
            'type' => $type,
            'title' => $title,
            'description' => $description,
            'event_date' => $eventDate,
            'question' => $type === 'FOUND' ? $question : null,
            'answer_hash' => $type === 'FOUND' ? password_hash($this->normalizeAnswer($answer), PASSWORD_DEFAULT) : null,
        ]);
        $itemId = (int) db()->lastInsertId();

        flash('Your item report has been published.');
        redirect('/items/' . $itemId);
    }

    private function showItem(int $id): void
    {
        $item = $this->findItem($id);
        $user = current_user();
        $canReviewSuspended = $user !== null && in_array($user['role'], ['moderator', 'admin'], true);
        if ($item === null || ($item['status'] === 'SUSPENDED' && !$canReviewSuspended)) {
            http_response_code(404);
            render('errors/not_found');
            return;
        }

        $matches = [];
        if ($item['status'] === 'ACTIVE') {
            $statement = db()->prepare(
                $this->itemSelect() . ' WHERE i.status = "ACTIVE" AND i.item_type <> :type AND i.id <> :id ORDER BY i.event_date DESC LIMIT 250'
            );
            $statement->execute(['type' => $item['item_type'], 'id' => $id]);
            foreach ($statement->fetchAll() as $candidate) {
                $score = MatchEngine::score($item, $candidate);
                if ($score >= MatchEngine::DISPLAY_THRESHOLD) {
                    $candidate['match_score'] = $score;
                    $matches[] = $candidate;
                }
            }
            usort($matches, static fn (array $a, array $b): int => $b['match_score'] <=> $a['match_score']);
            $matches = array_slice($matches, 0, 5);
        }

        $ownsItem = $user !== null && (int) $item['reporter_id'] === (int) $user['id'];
        $existingClaim = null;
        if ($user !== null) {
            $statement = db()->prepare(
                'SELECT id, status FROM claims
                 WHERE item_id = :item AND claimant_id = :claimant AND status IN ("PENDING", "APPROVED", "COMPLETED")
                 ORDER BY id DESC LIMIT 1'
            );
            $statement->execute(['item' => $id, 'claimant' => $user['id']]);
            $existingClaim = $statement->fetch() ?: null;
        }
        render('items/show', compact('item', 'matches', 'ownsItem', 'existingClaim'));
    }

    private function createClaim(int $itemId): void
    {
        $user = require_auth();
        verify_csrf();
        $answer = post_string('challenge_answer');
        $response = post_string('challenge_response');
        $item = $this->findItem($itemId);
        if ($item === null || $item['item_type'] !== 'FOUND' || $item['status'] !== 'ACTIVE') {
            flash('This item is not currently accepting claims.', 'danger');
            redirect('/items/' . $itemId);
        }
        if ((int) $item['reporter_id'] === (int) $user['id']) {
            flash('You cannot claim an item that you reported.', 'danger');
            redirect('/items/' . $itemId);
        }
        if (!password_verify($this->normalizeAnswer($answer), (string) $item['challenge_answer_hash'])) {
            flash('The hidden-feature answer did not match. Check with the finder if you need help.', 'danger');
            redirect('/items/' . $itemId);
        }
        if (mb_strlen($response) < 5 || mb_strlen($response) > 500) {
            flash('Add a short explanation to help the finder review your claim.', 'danger');
            redirect('/items/' . $itemId);
        }

        $database = db();
        $database->beginTransaction();
        try {
            $lock = $database->prepare('SELECT status FROM items WHERE id = :id FOR UPDATE');
            $lock->execute(['id' => $itemId]);
            if ($lock->fetchColumn() !== 'ACTIVE') {
                throw new \DomainException('This item is not currently accepting claims.');
            }
            $statement = $database->prepare(
                'INSERT INTO claims (item_id, claimant_id, challenge_response) VALUES (:item, :claimant, :response)'
            );
            $statement->execute(['item' => $itemId, 'claimant' => $user['id'], 'response' => $response]);
            $database->prepare('UPDATE items SET status = "CLAIM_PENDING" WHERE id = :id')->execute(['id' => $itemId]);
            $database->commit();
        } catch (Throwable $exception) {
            if ($database->inTransaction()) {
                $database->rollBack();
            }
            if ($exception instanceof PDOException && $exception->getCode() === '23000') {
                flash('You have already submitted a claim for this item.', 'warning');
                redirect('/items/' . $itemId);
            }
            if ($exception instanceof \DomainException) {
                flash($exception->getMessage(), 'warning');
                redirect('/items/' . $itemId);
            }
            throw $exception;
        }

        flash('Your claim was sent to the finder for review.');
        redirect('/claims');
    }

    private function showClaims(): void
    {
        $user = require_auth();
        $statement = db()->prepare(
            'SELECT cl.*, i.id AS item_id, i.title, i.item_type, i.reporter_id, claimant.full_name AS claimant_name,
                    claimant.email AS claimant_email, finder.full_name AS finder_name, finder.email AS finder_email
             FROM claims cl
             JOIN items i ON i.id = cl.item_id
             JOIN users claimant ON claimant.id = cl.claimant_id
             JOIN users finder ON finder.id = i.reporter_id
             WHERE cl.claimant_id = :claimant_id OR i.reporter_id = :reporter_id
             ORDER BY cl.created_at DESC'
        );
        $statement->execute(['claimant_id' => $user['id'], 'reporter_id' => $user['id']]);
        $claims = $statement->fetchAll();
        render('claims/index', compact('claims'));
    }

    private function reviewClaim(int $claimId): void
    {
        $user = require_auth();
        verify_csrf();
        $decision = post_string('decision');
        if (!in_array($decision, ['approve', 'reject'], true)) {
            flash('Choose approve or reject.', 'danger');
            redirect('/claims');
        }

        $database = db();
        $database->beginTransaction();
        try {
            $statement = $database->prepare(
                'SELECT cl.*, i.reporter_id, i.id AS item_id, i.status AS item_status
                 FROM claims cl JOIN items i ON i.id = cl.item_id WHERE cl.id = :id FOR UPDATE'
            );
            $statement->execute(['id' => $claimId]);
            $claim = $statement->fetch();
            if ($claim === false || (int) $claim['reporter_id'] !== (int) $user['id'] || $claim['status'] !== 'PENDING') {
                throw new \DomainException('This claim cannot be reviewed by your account.');
            }

            if ($decision === 'approve') {
                if ($claim['item_status'] !== 'CLAIM_PENDING') {
                    throw new \DomainException('The item is no longer available for approval.');
                }
                $pin = (string) random_int(1000, 9999);
                $update = $database->prepare(
                    'UPDATE claims SET status = "APPROVED", handover_pin_hash = :pin_hash, approved_at = NOW()
                     WHERE id = :id AND status = "PENDING"'
                );
                $update->execute(['pin_hash' => password_hash($pin, PASSWORD_DEFAULT), 'id' => $claimId]);
                $database->prepare(
                    'UPDATE claims SET status = "REJECTED" WHERE item_id = :item AND id <> :claim AND status = "PENDING"'
                )->execute(['item' => $claim['item_id'], 'claim' => $claimId]);
                $_SESSION['handover_pin_notice'] = ['claim_id' => $claimId, 'pin' => $pin];
            } else {
                $database->prepare('UPDATE claims SET status = "REJECTED" WHERE id = :id')->execute(['id' => $claimId]);
            }

            $remaining = $database->prepare('SELECT COUNT(*) FROM claims WHERE item_id = :item AND status = "PENDING"');
            $remaining->execute(['item' => $claim['item_id']]);
            if ((int) $remaining->fetchColumn() === 0 && $decision === 'reject') {
                $database->prepare('UPDATE items SET status = "ACTIVE" WHERE id = :id AND status = "CLAIM_PENDING"')
                    ->execute(['id' => $claim['item_id']]);
            }
            $database->commit();
        } catch (Throwable $exception) {
            if ($database->inTransaction()) {
                $database->rollBack();
            }
            if ($exception instanceof \DomainException) {
                flash($exception->getMessage(), 'warning');
                redirect('/claims');
            }
            throw $exception;
        }

        flash($decision === 'approve' ? 'Claim approved. Share the one-time handover PIN with the claimant.' : 'Claim rejected.');
        redirect('/claims');
    }

    private function completeClaim(int $claimId): void
    {
        $user = require_auth();
        verify_csrf();
        $pin = post_string('handover_pin');
        if (preg_match('/^\d{4}$/', $pin) !== 1) {
            flash('Enter the four-digit handover PIN.', 'danger');
            redirect('/claims');
        }

        $database = db();
        $database->beginTransaction();
        try {
            $statement = $database->prepare(
                'SELECT cl.*, i.reporter_id, i.id AS item_id, i.status AS item_status
                 FROM claims cl JOIN items i ON i.id = cl.item_id WHERE cl.id = :id FOR UPDATE'
            );
            $statement->execute(['id' => $claimId]);
            $claim = $statement->fetch();
            if (
                $claim === false || (int) $claim['reporter_id'] !== (int) $user['id']
                || $claim['status'] !== 'APPROVED' || $claim['item_status'] !== 'CLAIM_PENDING'
            ) {
                throw new \DomainException('This handover is not available.');
            }
            if (!password_verify($pin, (string) $claim['handover_pin_hash'])) {
                $attempts = (int) $claim['pin_attempts'] + 1;
                if ($attempts >= 5) {
                    $database->prepare('UPDATE claims SET pin_attempts = :attempts, status = "REJECTED" WHERE id = :id')
                        ->execute(['attempts' => $attempts, 'id' => $claimId]);
                    $database->prepare('UPDATE items SET status = "ACTIVE" WHERE id = :id')
                        ->execute(['id' => $claim['item_id']]);
                } else {
                    $database->prepare('UPDATE claims SET pin_attempts = :attempts WHERE id = :id')
                        ->execute(['attempts' => $attempts, 'id' => $claimId]);
                }
                $database->commit();
                flash('That handover PIN is incorrect.', 'danger');
                redirect('/claims');
            }
            $database->prepare('UPDATE claims SET status = "COMPLETED", completed_at = NOW() WHERE id = :id')
                ->execute(['id' => $claimId]);
            $database->prepare('UPDATE items SET status = "RESOLVED" WHERE id = :id')
                ->execute(['id' => $claim['item_id']]);
            $database->commit();
        } catch (Throwable $exception) {
            if ($database->inTransaction()) {
                $database->rollBack();
            }
            if ($exception instanceof \DomainException) {
                flash($exception->getMessage(), 'warning');
                redirect('/claims');
            }
            throw $exception;
        }
        flash('Handover confirmed. This item is now marked resolved.');
        redirect('/claims');
    }

    private function flagItem(int $itemId): void
    {
        $user = require_auth();
        verify_csrf();
        $reason = post_string('reason');
        if (mb_strlen($reason) < 5 || mb_strlen($reason) > 255) {
            flash('Please provide a brief reason for your report.', 'danger');
            redirect('/items/' . $itemId);
        }

        $database = db();
        $database->beginTransaction();
        try {
            $statement = $database->prepare('SELECT reporter_id, status FROM items WHERE id = :id FOR UPDATE');
            $statement->execute(['id' => $itemId]);
            $item = $statement->fetch();
            if ($item === false || (int) $item['reporter_id'] === (int) $user['id'] || $item['status'] === 'SUSPENDED') {
                throw new \DomainException('This item cannot be reported from your account.');
            }
            $database->prepare('INSERT INTO flags_log (item_id, user_id, reason) VALUES (:item, :user, :reason)')
                ->execute(['item' => $itemId, 'user' => $user['id'], 'reason' => $reason]);
            $database->prepare(
                'UPDATE items SET flags_count = (SELECT COUNT(*) FROM flags_log WHERE item_id = :item)
                 WHERE id = :id'
            )->execute(['item' => $itemId, 'id' => $itemId]);
            $database->prepare(
                'UPDATE items SET status = "SUSPENDED" WHERE id = :id AND flags_count >= 3 AND status <> "RESOLVED"'
            )->execute(['id' => $itemId]);
            $database->commit();
        } catch (Throwable $exception) {
            if ($database->inTransaction()) {
                $database->rollBack();
            }
            if ($exception instanceof PDOException && $exception->getCode() === '23000') {
                flash('You have already reported this listing.', 'warning');
                redirect('/items/' . $itemId);
            }
            if ($exception instanceof \DomainException) {
                flash($exception->getMessage(), 'warning');
                redirect('/items');
            }
            throw $exception;
        }
        flash('Thank you. The report has been recorded for moderation.');
        redirect('/items');
    }

    private function showModeration(): void
    {
        require_moderator();
        $items = db()->query(
            $this->itemSelect() . ' WHERE i.status = "SUSPENDED" OR i.flags_count > 0 ORDER BY i.flags_count DESC, i.updated_at DESC LIMIT 100'
        )->fetchAll();
        $flagStatement = db()->prepare(
            'SELECT f.reason, f.created_at, u.full_name FROM flags_log f JOIN users u ON u.id = f.user_id
             WHERE f.item_id = :id ORDER BY f.created_at DESC'
        );
        foreach ($items as &$item) {
            $flagStatement->execute(['id' => $item['id']]);
            $item['flags'] = $flagStatement->fetchAll();
        }
        unset($item);
        render('admin/index', compact('items'));
    }

    private function moderateItem(int $itemId): void
    {
        require_moderator();
        verify_csrf();
        $action = post_string('action');
        if (!in_array($action, ['suspend', 'restore'], true)) {
            flash('Choose a valid moderation action.', 'danger');
            redirect('/admin');
        }
        $status = 'SUSPENDED';
        if ($action === 'restore') {
            $pending = db()->prepare(
                'SELECT COUNT(*) FROM claims WHERE item_id = :item AND status IN ("PENDING", "APPROVED")'
            );
            $pending->execute(['item' => $itemId]);
            $status = (int) $pending->fetchColumn() > 0 ? 'CLAIM_PENDING' : 'ACTIVE';
        }
        $statement = db()->prepare('UPDATE items SET status = :status WHERE id = :id AND status <> "RESOLVED"');
        $statement->execute(['status' => $status, 'id' => $itemId]);
        flash($statement->rowCount() > 0 ? 'Moderation decision saved.' : 'The listing could not be changed.');
        redirect('/admin');
    }

    private function recentItems(int $limit): array
    {
        return db()->query(
            $this->itemSelect() . ' WHERE i.status IN ("ACTIVE", "CLAIM_PENDING") ORDER BY i.created_at DESC LIMIT ' . $limit
        )->fetchAll();
    }

    private function findItem(int $id): ?array
    {
        $statement = db()->prepare($this->itemSelect() . ' WHERE i.id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        return $statement->fetch() ?: null;
    }

    private function itemSelect(): string
    {
        return 'SELECT i.*, c.name AS category, v.name AS venue, v.building, u.full_name AS reporter_name
                FROM items i
                JOIN categories c ON c.id = i.category_id
                JOIN venues v ON v.id = i.venue_id
                JOIN users u ON u.id = i.reporter_id';
    }

    private function loadUser(int $id): array
    {
        $statement = db()->prepare('SELECT id, matric_number, department, full_name, email, role FROM users WHERE id = :id');
        $statement->execute(['id' => $id]);
        $user = $statement->fetch();
        if ($user === false) {
            throw new \RuntimeException('Unable to load the authenticated account.');
        }
        return $user;
    }

    private function normalizeAnswer(string $answer): string
    {
        return strtolower((string) preg_replace('/\s+/u', ' ', trim($answer)));
    }
}
