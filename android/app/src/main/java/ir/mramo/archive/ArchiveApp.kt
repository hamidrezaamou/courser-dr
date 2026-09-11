package ir.mramo.archive

import android.app.Application
import ir.mramo.archive.data.ArchiveRepository
import ir.mramo.archive.data.SessionStore

class ArchiveApp : Application() {
    lateinit var session: SessionStore
        private set
    lateinit var repository: ArchiveRepository
        private set

    override fun onCreate() {
        super.onCreate()
        session = SessionStore(this)
        repository = ArchiveRepository(session)
    }
}
