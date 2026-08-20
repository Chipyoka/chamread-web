@auth
<div id="idle-modal" class="idle-modal-overlay" style="display:none;">
    <div class="idle-modal-box border border-amber-400">
        <h3 class="text-amber-500 font-semibold">Are you still there?</h3>
        <p class="text-sm">You've been inactive. For your security, you'll be logged out in
            <span id="idle-countdown">60</span> seconds.
        </p>
        <div class="idle-modal-actions">
            <button id="idle-stay-btn" class="bg-amber-500 hover:opacity-90" type="button">Stay logged in</button>
        </div>
    </div>
</div>

<form id="idle-logout-form" action="{{ route('logout') }}" method="POST" style="display:none;">
    @csrf
</form>

<style>
.idle-modal-overlay {
    position: fixed; inset: 0; background: rgba(0,0,0,0.5);
    display: flex; align-items: center; justify-content: center; z-index: 9999;
}
.idle-modal-box {
    background: #fff; padding: 24px 28px; border-radius: 8px;
    max-width: 380px; width: 90%; box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    font-family: inherit;
}
.idle-modal-box h3 { margin: 0 0 10px; font-size: 18px; }
.idle-modal-box p { margin: 0 0 16px; color: #444; }
.idle-modal-actions { text-align: right; }
#idle-stay-btn {
    color: #fff; border: none; padding: 8px 16px;
    border-radius: 6px; cursor: pointer; font-size: 14px;
}
</style>

<script>
(function () {
    // ==== CONFIG ====
    const IDLE_TIMEOUT_MS   = 20 * 60 * 1000; // time of inactivity before warning shows (20 min)
    const COUNTDOWN_SECONDS = 60;             // countdown shown in modal before auto logout

    const modal        = document.getElementById('idle-modal');
    const countdownEl  = document.getElementById('idle-countdown');
    const stayBtn      = document.getElementById('idle-stay-btn');
    const logoutForm   = document.getElementById('idle-logout-form');

    let idleTimer, countdownTimer, secondsLeft;

    function startIdleTimer() {
        clearTimeout(idleTimer);
        idleTimer = setTimeout(showWarning, IDLE_TIMEOUT_MS);
    }

    function showWarning() {
        secondsLeft = COUNTDOWN_SECONDS;
        countdownEl.textContent = secondsLeft;
        modal.style.display = 'flex';

        countdownTimer = setInterval(() => {
            secondsLeft--;
            countdownEl.textContent = secondsLeft;
            if (secondsLeft <= 0) {
                clearInterval(countdownTimer);
                logoutForm.submit();
            }
        }, 1000);
    }

    function resetAll() {
        modal.style.display = 'none';
        clearInterval(countdownTimer);
        startIdleTimer();
    }

    stayBtn.addEventListener('click', resetAll);

    // Any of these user actions count as "active" and reset the idle timer,
    // but only while the warning modal isn't showing (so it doesn't
    // dismiss itself just because the mouse moved over the overlay).
    ['mousemove', 'keydown', 'click', 'scroll', 'touchstart'].forEach(evt => {
        document.addEventListener(evt, () => {
            if (modal.style.display === 'none' || modal.style.display === '') {
                startIdleTimer();
            }
        });
    });

    startIdleTimer();
})();
</script>
@endauth