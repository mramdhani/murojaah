/**
 * Composable for making API calls to the Murojaah Laravel backend
 */
export const useApi = () => {
  const config = useRuntimeConfig()
  const apiBase = config.public.apiBase as string
  const { token, initSession, user } = useAuth()

  const apiFetch = async <T>(endpoint: string, options: RequestInit = {}): Promise<T> => {
    const url = `${apiBase}${endpoint}`

    // If we have no token, try re-initializing session first
    if (!token.value) {
      await initSession()
    }

    const makeRequest = async (authToken: string | null): Promise<Response> => {
      const headers: Record<string, string> = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      }
      if (authToken) {
        headers['Authorization'] = `Bearer ${authToken}`
      }
      if (options.headers) {
        Object.assign(headers, options.headers)
      }
      return fetch(url, {
        headers,
        ...options,
      })
    }

    let response = await makeRequest(token.value)

    if (!response.ok) {
      // If 401, clear token, re-init session, and retry once
      if (response.status === 401) {
        token.value = null
        user.value = null
        await initSession()
        // Retry with the new token
        response = await makeRequest(token.value)
      }

      if (!response.ok) {
        throw new Error(`API Error: ${response.status} ${response.statusText}`)
      }
    }

    return response.json()
  }

  return { apiFetch }
}
