// Modelos de dominio. Deben coincidir con los API Resources de Laravel.
export const INCIDENT_STATES = ['Recibida', 'En revisión', 'Asignada', 'En gestión', 'Resuelta', 'Cerrada'] as const;
export type IncidentStatus = typeof INCIDENT_STATES[number];
export type Priority = 'Alta' | 'Media' | 'Baja';
export type Role = 'superadmin' | 'admin' | 'propietario' | 'residente' | 'seguridad' | 'mantenimiento' | 'junta';

export interface User { id: number; name: string; email: string; role: Role; unit?: string; unit_id?: number | null }
export interface Evidence { id?: number; url?: string; label: string; mime?: string | null }
export interface IncidentEntry { who: string; when: string; kind?: 'res' | 'int' | 'acc'; text: string }

export interface Incident {
  id: string; title: string; type: string; location: string; description: string;
  priority: Priority; status: IncidentStatus; assignee: string | null; reporter: string; unit?: string;
  evidence: Evidence[]; solution: Evidence[]; recurring?: boolean; created_at: string; age?: string;
  timeline?: IncidentEntry[]; location_type?: string | null;
}
export interface NewIncident { title: string; type: string; location: string; description: string; priority: Priority; files: File[] }

export type ChargeStatus = 'Al día' | 'Por vencer' | 'Vencido' | 'Moroso' | 'Legal';
export interface Charge { id: number; unit_id: number; unit: string; resident: string; concept: string; amount: number; balance: number; due_date: string; status: ChargeStatus }

export interface Notice { id: number; category: string; title: string; date: string; body: string; read: boolean }

export interface Visitor { id: number; name: string; document: string; unit: string; host: string; plate?: string; entered_at: string; left_at: string | null }

export interface DashboardSummary {
  billed_month: number; collected_month: number; collection_rate: number;
  open_incidents: number; overdue_units: number; visitors_today: number;
  monthly: { label: string; a: number; b: number }[];
}

export interface Flash { success?: string | null; error?: string | null }
export interface PageProps { auth: { user: User }; flash: Flash; community: { name: string; meta: string }; [key: string]: unknown }

export interface Paginated<T> { data: T[]; meta?: { current_page: number; last_page: number; total: number } }

export type Occupancy = 'propietario' | 'alquilado' | 'vacante';
export interface Community {
  id: number; name: string; address: string | null; status: 'activo' | 'prospecto' | 'inactivo'; plan: string | null;
  units_count: number; loaded_units: number; occupied_units: number; units_with_balance: number; open_incidents: number; balance: number; maintenance_fee: number;
}
export interface UnitSummary { id: number; code: string; number: string; occupancy: Occupancy; balance: number }
export interface Block { id: number; code: string; name: string; buildings: number; units: number; incidents: number }
export interface BuildingSummary { id: number; code: string; name: string; units: number }
export interface Street { id: number; code: string; name: string; reference: string | null; block: string; lighting_points: number }
export interface Person { id: number; name: string; relation: 'propietario' | 'residente' | 'inquilino'; document_id: string | null; phone: string | null }
export interface UnitDetail {
  id: number; code: string; number: string; floor: number | null; community: { id: number; name: string }; block: string | null; building: string | null;
  occupancy: Occupancy; balance: number; maintenance_fee: number; parking: string | null; move_in_date: string | null;
  emergency_contact: { name: string; relation: string; phone: string } | null; vehicles: { plate: string; type: string; color: string; parking?: string }[]; notes: string | null;
}
export interface CaseRow { id: string; title: string; status: string; priority: Priority }

export interface IncidentDetail extends Incident {
  community: string | null; scope: string | null; assignee_id: number | null; assignee_role: string | null;
  first_response_at: string | null; closed_at: string | null; response_minutes: number | null; resolution_minutes: number | null;
  resolution: string | null; recurrence_note: string | null; resident_confirmed_at: string | null; resident_feedback: string | null; admin_notes: string | null;
}
export interface IncidentStats { total: number; open: number; with_evidence: number; closed_documented: number; avg_response_minutes: number | null; by_type: Record<string, number> }

export interface ServiceAsset {
  id: number; category: string; name: string; location: string | null; status: string; status_label: string; health: number; availability: number | null;
  capacity: string | null; reading_label: string | null; reading: number | null; responsible: string | null; provider: string | null; routine: string[]; risk: string | null;
  last_maintenance: string | null; next_maintenance: string | null; next_maintenance_id: number | null; overdue: boolean;
}
export interface UpcomingMaintenance { id: number; asset_id: number; asset: string; location: string | null; category: string; responsible: string | null; date: string; type: 'preventivo' | 'correctivo'; overdue: boolean }
export interface LogEntry { id: number; action: string; detail: string | null; who: string | null; at: string }

export interface PaymentRow { id: number; unit: string | null; concept: string | null; amount: number; method: string; reference: string | null; date: string }
export interface UnitBalance { id: number; code: string; building: string | null; owner: string | null; maintenance_fee: number; balance: number; status: ChargeStatus }
export interface Page<T> { data: T[]; meta: { current_page: number; last_page: number; total: number } }
export interface FinanceStats { receivable: number; collected_month: number; on_time: number; in_debt: number; legal: number }
export interface ManagedUser { id: number; name: string; email: string; role: Role; role_label: string; phone: string | null; document_id: string | null; active: boolean; units: { unit_id: number; code: string; relation: 'propietario' | 'residente' | 'inquilino' }[] }
