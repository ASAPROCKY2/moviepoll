<?php
require_once 'auth.php';
require_once 'functions.php';

requireLogin();

$movies = getMovies();
?>

<?php $page_title = 'Manage Movies'; ?>
<?php include 'header.php'; ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Manage Movies</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="add_movie.php" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-plus-circle"></i> Add Movie
        </a>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Release Date</th>
                <th>Votes</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($movies as $movie): ?>
            <tr>
                <td><?php echo $movie['id']; ?></td>
                <td><?php echo htmlspecialchars($movie['title']); ?></td>
                <td><?php echo $movie['release_date'] ? date('M d, Y', strtotime($movie['release_date'])) : 'N/A'; ?></td>
                <td><?php echo $movie['votes']; ?></td>
                <td>
                    <a href="edit_movie.php?id=<?php echo $movie['id']; ?>" class="btn btn-sm btn-warning">
                        <i class="bi bi-pencil"></i> Edit
                    </a>
                    <a href="delete_movie.php?id=<?php echo $movie['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
                        <i class="bi bi-trash"></i> Delete
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include 'footer.php'; ?>