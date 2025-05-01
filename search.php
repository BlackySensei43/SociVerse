<?php
include 'includes/config.php';
include 'includes/header.php';

$search_term = isset($_GET['q']) ? sanitize($_GET['q']) : '';

?>
<div class="container mt-5">
    <div class="card shadow mb-4">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0"><i class="fas fa-search"></i> بحث عن مستخدمين</h4>
        </div>
        <div class="card-body">
            <form method="GET" action="search.php">
                <div class="input-group">
                    <input type="text" name="q" class="form-control" 
                           placeholder="ابحث باسم المستخدم..." value="<?= $search_term ?>">
                    <button class="btn btn-primary" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
            
            <?php
            if (!empty($search_term)) {
                $stmt = $conn->prepare("SELECT * FROM users 
                                      WHERE username LIKE ? OR fullname LIKE ?
                                      LIMIT 20");
                $search_term = "%$search_term%";
                $stmt->bind_param("ss", $search_term, $search_term);
                $stmt->execute();
                $results = $stmt->get_result();
                
                if ($results->num_rows > 0) {
                    echo '<div class="row mt-4">';
                    while($user = $results->fetch_assoc()) {
                        echo '<div class="col-md-4 mb-3">
                                <div class="card">
                                    <div class="card-body text-center">
                                        <a href="profile.php?user='.$user['id'].'">
                                            <img src="uploads/'.$user['profile_pic'].'" 
                                                 class="rounded-circle mb-2" 
                                                 width="80" height="80">
                                            <h5>'.$user['fullname'].'</h5>
                                            <p class="text-muted">@'.$user['username'].'</p>
                                        </a>
                                    </div>
                                </div>
                              </div>';
                    }
                    echo '</div>';
                } else {
                    echo '<p class="text-muted mt-3">لا توجد نتائج</p>';
                }
            }
            ?>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>