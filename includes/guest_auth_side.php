<!-- left welcome panel on the guest login and sign-up pages (uses $side_title and $side_text) -->
<aside class="auth-side">
  <a href="/HMS-Project/Guest/rooms.php" class="brand">Stay<span>Ease</span></a>

  <h2 class="side-title"><?php echo htmlspecialchars($side_title); ?></h2>
  <p class="side-text"><?php echo htmlspecialchars($side_text); ?></p>

  <ul class="perks">
    <li>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
      See which rooms are free for your dates
    </li>
    <li>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
      Total price worked out before you book
    </li>
    <li>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/></svg>
      Cancel any time before check-in
    </li>
  </ul>
</aside>
