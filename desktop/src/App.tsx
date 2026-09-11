import { useState } from "react";
import { LoginScreen } from "./screens/LoginScreen";
import { Shell } from "./screens/Shell";
import { clearSession, loadSession, saveSession } from "./lib/session";
import type { Session } from "./lib/types";

export default function App() {
  const [session, setSession] = useState<Session | null>(() => loadSession());

  if (!session) {
    return <LoginScreen onLoggedIn={setSession} />;
  }

  return (
    <Shell
      session={session}
      onSession={(next) => {
        saveSession(next);
        setSession(next);
      }}
      onLogout={() => {
        clearSession();
        setSession(null);
      }}
    />
  );
}
