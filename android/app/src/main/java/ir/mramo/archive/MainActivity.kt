package ir.mramo.archive

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.activity.enableEdgeToEdge
import androidx.compose.foundation.layout.padding
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.outlined.EventNote
import androidx.compose.material.icons.outlined.Home
import androidx.compose.material.icons.outlined.People
import androidx.compose.material.icons.outlined.Settings
import androidx.compose.material3.Icon
import androidx.compose.material3.NavigationBar
import androidx.compose.material3.NavigationBarItem
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.navigation.NavType
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.compose.currentBackStackEntryAsState
import androidx.navigation.compose.rememberNavController
import androidx.navigation.navArgument
import ir.mramo.archive.data.UserDto
import ir.mramo.archive.ui.board.BoardScreen
import ir.mramo.archive.ui.booking.BookSurgeryScreen
import ir.mramo.archive.ui.booking.BookVisitScreen
import ir.mramo.archive.ui.home.HomeScreen
import ir.mramo.archive.ui.login.LoginScreen
import ir.mramo.archive.ui.patients.CreatePatientScreen
import ir.mramo.archive.ui.patients.ExamScreen
import ir.mramo.archive.ui.patients.PatientDetailScreen
import ir.mramo.archive.ui.patients.PatientsScreen
import ir.mramo.archive.ui.settings.SettingsScreen
import ir.mramo.archive.ui.theme.ArchiveTheme

class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        enableEdgeToEdge()
        val app = application as ArchiveApp
        setContent {
            ArchiveTheme {
                ArchiveRoot(app)
            }
        }
    }
}

private data class Tab(val route: String, val label: String, val icon: androidx.compose.ui.graphics.vector.ImageVector)

@Composable
private fun ArchiveRoot(app: ArchiveApp) {
    val nav = rememberNavController()
    val repo = app.repository
    val session = app.session
    var ready by remember { mutableStateOf(false) }
    var loggedIn by remember { mutableStateOf(false) }
    var user by remember { mutableStateOf<UserDto?>(null) }

    LaunchedEffect(Unit) {
        repo.hydrate()
        val token = session.currentToken()
        loggedIn = token.isNotBlank()
        if (loggedIn) {
            runCatching { repo.home() }.onSuccess {
                user = it.user
            }.onFailure {
                loggedIn = false
            }
        }
        ready = true
    }

    if (!ready) return

    if (!loggedIn) {
        LoginScreen(repo, session) { logged ->
            user = logged
            loggedIn = true
        }
        return
    }

    val staff = user?.isStaff == true
    val tabs = buildList {
        add(Tab("home", "خانه", Icons.Outlined.Home))
        if (staff) {
            add(Tab("patients", "بیماران", Icons.Outlined.People))
            add(Tab("board", "بُرد", Icons.Outlined.EventNote))
        }
        add(Tab("more", "حساب", Icons.Outlined.Settings))
    }

    Scaffold(
        bottomBar = {
            val route = nav.currentBackStackEntryAsState().value?.destination?.route.orEmpty()
            val show = tabs.any { route == it.route }
            if (show) {
                NavigationBar {
                    tabs.forEach { tab ->
                        NavigationBarItem(
                            selected = route == tab.route,
                            onClick = {
                                nav.navigate(tab.route) {
                                    popUpTo("home") { saveState = true }
                                    launchSingleTop = true
                                    restoreState = true
                                }
                            },
                            icon = { Icon(tab.icon, contentDescription = tab.label) },
                            label = { Text(tab.label) },
                        )
                    }
                }
            }
        },
    ) { padding ->
        NavHost(nav, startDestination = "home", modifier = Modifier.padding(padding)) {
            composable("home") {
                LaunchedEffect(Unit) {
                    runCatching { repo.home() }.onSuccess { user = it.user }
                }
                HomeScreen(repo) { id -> nav.navigate("patient/$id") }
            }
            composable("patients") {
                PatientsScreen(
                    repo = repo,
                    onOpen = { nav.navigate("patient/$it") },
                    onCreate = { nav.navigate("create_patient") },
                )
            }
            composable("board") {
                BoardScreen(repo) { id -> nav.navigate("patient/$id") }
            }
            composable("more") {
                SettingsScreen(repo, session, user) {
                    loggedIn = false
                    user = null
                }
            }
            composable(
                "patient/{id}",
                arguments = listOf(navArgument("id") { type = NavType.LongType }),
            ) { entry ->
                val id = entry.arguments?.getLong("id") ?: return@composable
                PatientDetailScreen(
                    repo = repo,
                    patientId = id,
                    user = user,
                    onBack = { nav.popBackStack() },
                    onBookVisit = { nav.navigate("book_visit/$it") },
                    onBookSurgery = { nav.navigate("book_surgery/$it") },
                    onExam = { nav.navigate("exam/$it") },
                )
            }
            composable("create_patient") {
                CreatePatientScreen(
                    repo = repo,
                    onBack = { nav.popBackStack() },
                    onCreated = { id ->
                        nav.popBackStack()
                        nav.navigate("patient/$id")
                    },
                )
            }
            composable(
                "exam/{id}",
                arguments = listOf(navArgument("id") { type = NavType.LongType }),
            ) { entry ->
                val id = entry.arguments?.getLong("id") ?: return@composable
                ExamScreen(repo, id, onBack = { nav.popBackStack() }, onSaved = { nav.popBackStack() })
            }
            composable(
                "book_visit/{id}",
                arguments = listOf(navArgument("id") { type = NavType.LongType }),
            ) { entry ->
                val id = entry.arguments?.getLong("id") ?: return@composable
                BookVisitScreen(repo, id, onBack = { nav.popBackStack() }, onDone = { nav.popBackStack() })
            }
            composable(
                "book_surgery/{id}",
                arguments = listOf(navArgument("id") { type = NavType.LongType }),
            ) { entry ->
                val id = entry.arguments?.getLong("id") ?: return@composable
                BookSurgeryScreen(repo, id, onBack = { nav.popBackStack() }, onDone = { nav.popBackStack() })
            }
        }
    }
}
