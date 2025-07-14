<?php

/** @var string $title */
/** @var array $users */
/** @var array|null $response */

$title = "Gửi thông báo đến người dùng";
?>

<div class="container py-4">
    <h1 class="text-primary mb-4"><i class="bi bi-megaphone"></i> Gửi thông báo</h1>

    <?php if ($response): ?>
        <div class="alert <?= $response['status'] ? 'alert-success' : 'alert-danger' ?>">
            <?= htmlspecialchars($response['message']) ?>
        </div>

        <?php if (isset($response['results'])): ?>
            <ul class="list-group mb-3">
                <?php foreach ($response['results'] as $res): ?>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>ID: <?= $res['account_id'] ?></span>
                        <span class="badge bg-<?= $res['status'] === 'sent' ? 'success' : 'danger' ?>">
                            <?= $res['status'] ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label for="message" class="form-label">Nội dung thông báo</label>
            <textarea class="form-control" id="message" name="message" rows="3" required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
        </div>

        <div class="mb-3">
            <label for="target_id" class="form-label">Gửi đến</label>
            <select class="form-select" id="target_id" name="target_id">
                <option value="all" <?= (!isset($_POST['target_id']) || $_POST['target_id'] === 'all') ? 'selected' : '' ?>>Tất cả người dùng</option>
                <?php foreach ($users as $user): ?>
                    <option value="<?= $user['account_id'] ?>" <?= (isset($_POST['target_id']) && $_POST['target_id'] == $user['account_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($user['full_name'] . ' (' . $user['username'] . ')') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="btn btn-primary"><i class="bi bi-send"></i> Gửi thông báo</button>
    </form>
</div>