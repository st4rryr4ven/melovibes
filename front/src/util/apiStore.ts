export const apiStore = {
  apiUrl: "https://localhost/site_de_musique/api/public/api/",

  getAll(ressource: string): Promise<unknown> {

    return fetch(this.apiUrl + ressource)
      .then(reponsehttp => reponsehttp.json())
      .then(data => data.member);
  },
  login(login: string, password: string): Promise<unknown> {
    return fetch("https://localhost/site_de_musique/api/public/api/auth", {
      method: "POST",
      headers: {
        'Content-Type': 'application/json'
      },
      credentials: 'include',
      body: JSON.stringify({login: login, password: password}),
    })
  }
}
