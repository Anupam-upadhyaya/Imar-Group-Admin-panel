<?php
define('SECURE_ACCESS', true);
require_once __DIR__ . '/../config/config.php';
include_once __DIR__ . '/../includes/header.php';
include_once __DIR__ . '/../includes/sidebar.php';
$id=intval($_GET['id']??0);
$conn=Database::getInstance()->getConnection();
$stmt=$conn->prepare("SELECT * FROM blog_posts WHERE id=?");
$stmt->bind_param('i',$id);
$stmt->execute();
$post=$stmt->get_result()->fetch_assoc();
?>
<div class="main-content">
  <div class="dashboard-header"><h1>Edit Blog Post</h1></div>

  <div class="content-card">
    <form id="blogForm" enctype="multipart/form-data">
      <input type="hidden" name="id" value="<?= $post['id']; ?>">
      <div class="input-group"><label>Title</label><input type="text" name="title" value="<?= htmlspecialchars($post['title']); ?>"></div>
      <div class="input-group"><label>Slug</label><input type="text" name="slug" value="<?= htmlspecialchars($post['slug']); ?>"></div>
      <div class="input-group"><label>Category</label>
        <select name="category">
          <?php foreach(['tax-planning','investment','retirement','news'] as $cat): ?>
          <option value="<?= $cat ?>" <?= $post['category']==$cat?'selected':''; ?>><?= ucfirst($cat) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="input-group"><label>Excerpt</label><textarea name="excerpt"><?= htmlspecialchars($post['excerpt']); ?></textarea></div>
      <div class="input-group"><label>Featured Image (leave blank to keep existing)</label><input type="file" name="featured_image"></div>
      <div class="input-group"><label>Content</label><textarea id="content" name="content"><?= htmlspecialchars($post['content']); ?></textarea></div>
      <div class="input-group"><label>Meta Title</label><input type="text" name="meta_title" value="<?= htmlspecialchars($post['meta_title']); ?>"></div>
      <div class="input-group"><label>Meta Description</label><textarea name="meta_description"><?= htmlspecialchars($post['meta_description']); ?></textarea></div>
      <button type="submit" class="login-btn">Update Post</button>
    </form>
  </div>
</div>

<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js"></script>
<script>
tinymce.init({ selector:'#content', height:400, menubar:false });
document.getElementById('blogForm').addEventListener('submit',async e=>{
  e.preventDefault();
  const fd=new FormData(e.target);
  const res=await fetch('../../API/save-blog.php',{method:'POST',body:fd});
  const data=await res.json();
  alert(data.message);
  if(data.success)window.location='manage-blog.php';
});
</script>
<?php include_once __DIR__ . '/../includes/footer.php'; ?>
