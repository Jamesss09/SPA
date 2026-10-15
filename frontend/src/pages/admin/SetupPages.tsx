import { useEffect, useState } from "react";
import CrudPage from "./CrudPage";
import { api } from "../../lib/api";

export function SchoolYears() {
  return (
    <CrudPage
      title="School years"
      path="/school-years"
      columns={[
        { key: "name", label: "Name" },
        { key: "start_date", label: "Start" },
        { key: "end_date", label: "End" },
        { key: "status", label: "Status" },
      ]}
      fields={[
        { key: "name", label: "Name", required: true },
        { key: "start_date", label: "Start date", type: "date" },
        { key: "end_date", label: "End date", type: "date" },
        { key: "status", label: "Status", type: "select", options: [{ value: "active", label: "active" }, { value: "inactive", label: "inactive" }, { value: "archived", label: "archived" }] },
      ]}
    />
  );
}

export function useOptions(path: string, valueKey: string, labelKey: string) {
  const [opts, setOpts] = useState<{ value: string | number; label: string }[]>([]);
  useEffect(() => {
    api.get(path, { params: { per_page: 100 } }).then(({ data }) => {
      const rows = Array.isArray(data) ? data : (data.data ?? []);
      setOpts(rows.map((r: Record<string, unknown>) => ({ value: r[valueKey] as string | number, label: String(r[labelKey]) })));
    }).catch(() => undefined);
  }, [path, valueKey, labelKey]);
  return opts;
}

export function GradingPeriods() {
  const years = useOptions("/school-years", "id", "name");
  return (
    <CrudPage
      title="Grading periods"
      path="/grading-periods"
      columns={[
        { key: "quarter_number", label: "Quarter" },
        { key: "school_year_id", label: "Year ID" },
        { key: "status", label: "Status" },
      ]}
      fields={[
        { key: "school_year_id", label: "School year", type: "select", options: years, required: true },
        { key: "quarter_number", label: "Quarter (1–4)", type: "number", required: true },
        { key: "start_date", label: "Start date", type: "date" },
        { key: "end_date", label: "End date", type: "date" },
      ]}
    />
  );
}

export function GradeLevels() {
  return (
    <CrudPage
      title="Grade levels"
      path="/grade-levels"
      columns={[
        { key: "name", label: "Name" },
        { key: "level_number", label: "Level" },
      ]}
      fields={[
        { key: "name", label: "Name", required: true },
        { key: "level_number", label: "Level (7–12)", type: "number", required: true },
      ]}
    />
  );
}

export function Rooms() {
  return (
    <CrudPage
      title="Rooms"
      path="/rooms"
      columns={[
        { key: "room_name", label: "Room" },
        { key: "capacity", label: "Capacity" },
        { key: "status", label: "Status" },
      ]}
      fields={[
        { key: "room_name", label: "Room name", required: true },
        { key: "capacity", label: "Capacity", type: "number" },
      ]}
    />
  );
}

export function Subjects() {
  const levels = useOptions("/grade-levels", "id", "name");
  return (
    <CrudPage
      title="Subjects"
      path="/subjects"
      columns={[
        { key: "subject_code", label: "Code" },
        { key: "subject_name", label: "Name" },
        { key: "grade_level_id", label: "Level ID" },
        { key: "status", label: "Status" },
      ]}
      fields={[
        { key: "subject_code", label: "Code", required: true },
        { key: "subject_name", label: "Name", required: true },
        { key: "grade_level_id", label: "Grade level", type: "select", options: levels },
      ]}
    />
  );
}
