// Mirrors the JSON shapes returned by the Laravel API Resources.

export type Sector = 'healthcare' | 'education' | 'charity' | 'carer'
export type VerificationStatus = 'pending' | 'verified' | 'rejected'
export type RequestStatus = 'pending' | 'approved' | 'rejected'

export const SECTORS: Sector[] = ['healthcare', 'education', 'charity', 'carer']

export interface User {
  id: number
  name: string
  email: string
  sector: Sector | null
  verification_status: VerificationStatus
  is_admin: boolean
  created_at: string
}

export interface Brand {
  id: number
  name: string
  description: string
  website: string | null
  logo_url: string | null
}

export interface Offer {
  id: number
  title: string
  description: string
  sector: Sector
  discount_description: string
  starts_at: string
  expires_at: string
  is_active: boolean
  max_redemptions: number | null
  redemption_count: number
  brand?: Brand
}

export interface Redemption {
  id: number
  code_issued: string
  redeemed_at: string
  offer?: Offer
}

export interface VerificationRequest {
  id: number
  proof_reference: string
  status: RequestStatus
  reviewed_at: string | null
  created_at: string
  user?: User
}
