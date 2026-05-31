<?php
// ============================================================
// ANIME VAULT - Common Footer Include
// File: /var/www/html/anime/includes/footer.php
// ============================================================
?>

<footer class="footer">
  <div class="container">
    <div class="footer-inner">
      <div class="footer-brand">
        <h3>Anime<span class="text-pink">Vault</span></h3>
        <p>Your definitive anime database. Browse thousands of titles, leave reviews, and track everything you've watched.</p>
        <p style="margin-top:0.75rem;">
          <a href="https://osztromok.com" style="color:var(--text-secondary);font-size:0.85rem;">← Back to osztromok.com</a>
        </p>
      </div>

      <div class="footer-col">
        <h4>Browse</h4>
        <ul>
          <li><a href="/anime/browse.php">All Anime</a></li>
          <li><a href="/anime/browse.php?type=Movie">Movies</a></li>
          <li><a href="/anime/browse.php?sort=rating">Top Rated</a></li>
          <li><a href="/anime/browse.php?status=Airing">Currently Airing</a></li>
          <li><a href="/anime/browse.php?status=Upcoming">Upcoming</a></li>
        </ul>
      </div>

      <div class="footer-col">
        <h4>Account</h4>
        <ul>
          <li><a href="/anime/login.php">Login</a></li>
          <li><a href="/anime/register.php">Register</a></li>
          <li><a href="/anime/watchlist.php">My Watchlist</a></li>
        </ul>
      </div>

      <div class="footer-col">
        <h4>Site</h4>
        <ul>
          <li><a href="https://osztromok.com">Main Site</a></li>
        </ul>
      </div>
    </div>

    <div class="footer-bottom">
      <span>&copy; <?= date('Y') ?> Anime Vault – part of osztromok.com</span>
      <span>Built with ❤ and late nights</span>
    </div>
  </div>
</footer>

<script src="/anime/js/main.js"></script>
</body>
</html>
