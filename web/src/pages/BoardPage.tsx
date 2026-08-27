import { Board } from '../features/board/Board';
import { useAuth } from '../auth/useAuth';

export function BoardPage() {
  const { user, logout } = useAuth();

  return (
    <div className="app-shell">
      <header className="app-header">
        <h1>Throughline</h1>
        <div className="app-header__actions">
          <span className="app-header__user">{user?.name}</span>
          <button type="button" onClick={() => logout()}>
            Log out
          </button>
        </div>
      </header>
      <main>
        <Board />
      </main>
    </div>
  );
}
