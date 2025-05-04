/**
 * Comprueba si un string cumple un formato básico de correo electrónico.
 * @param {string} email
 * @returns {boolean}
 */
export function validateEmail(email) {
    // Regex simple para validar email
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

export function validatePassword(password) {
    // Verifica si la contraseña tiene al menos 8 caracteres
    return password.length >= 8;
}
  
  /**
   * Valida los campos de login:
   *  - Ninguno puede estar vacío.
   *  - El email debe tener formato válido.
   * @param {string} email
   * @param {string} password
   * @returns {{ valid: boolean, message?: string }}
   */
export function validarCamposLogin(email, password) {

    if (!email.trim() || !password.trim()) {
      return {
        valid: false,
        message: 'Los campos no pueden estar vacíos.',
      };
    }
  
    if (!validateEmail(email)) {
      return {
        valid: false,
        message: 'El correo electrónico no tiene un formato válido.',
      };
    }

    if (!validatePassword(password)) {
      return {
        valid: false,
        message: 'La contraseña debe tener al menos 8 caracteres.',
      };
    }
  
    return { valid: true };

}
  