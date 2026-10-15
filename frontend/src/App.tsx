import { BrowserRouter, Routes, Route, Navigate } from "react-router-dom";
import { AuthProvider } from "./auth/AuthContext";
import Layout from "./components/Layout";
import ProtectedRoute from "./components/ProtectedRoute";
import Login from "./pages/Login";
import ChangePassword from "./pages/ChangePassword";
import AdminDashboard from "./pages/AdminDashboard";
import FacultyDashboard from "./pages/FacultyDashboard";
import StudentDashboard from "./pages/StudentDashboard";
import { SchoolYears, GradingPeriods, GradeLevels, Rooms, Subjects } from "./pages/admin/SetupPages";
import Sections from "./pages/admin/Sections";
import Users from "./pages/admin/Users";

const admin = (el: React.ReactNode) => <ProtectedRoute role="admin">{el}</ProtectedRoute>;

export default function App() {
  return (
    <AuthProvider>
      <BrowserRouter>
        <Layout>
          <Routes>
            <Route path="/login" element={<Login />} />
            <Route path="/change-password" element={<ChangePassword />} />
            <Route path="/admin" element={admin(<AdminDashboard />)} />
            <Route path="/admin/school-years" element={admin(<SchoolYears />)} />
            <Route path="/admin/grading-periods" element={admin(<GradingPeriods />)} />
            <Route path="/admin/grade-levels" element={admin(<GradeLevels />)} />
            <Route path="/admin/sections" element={admin(<Sections />)} />
            <Route path="/admin/rooms" element={admin(<Rooms />)} />
            <Route path="/admin/subjects" element={admin(<Subjects />)} />
            <Route path="/admin/users" element={admin(<Users />)} />
            <Route path="/faculty" element={<ProtectedRoute role="faculty"><FacultyDashboard /></ProtectedRoute>} />
            <Route path="/student" element={<ProtectedRoute role="student"><StudentDashboard /></ProtectedRoute>} />
            <Route path="*" element={<Navigate to="/login" replace />} />
          </Routes>
        </Layout>
      </BrowserRouter>
    </AuthProvider>
  );
}
