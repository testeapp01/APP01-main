// utils/validators.js

export function validateCPF(cpf) {
  cpf = cpf.replace(/\D/g, '');
  if (cpf.length !== 11) return false;
  // Validação básica: todos iguais
  if (/^(\d)\1+$/.test(cpf)) return false;
  let sum = 0, rest;
  for (let i = 1; i <= 9; i++) sum += parseInt(cpf[i-1]) * (11 - i);
  rest = (sum * 10) % 11;
  if (rest === 10 || rest === 11) rest = 0;
  if (rest !== parseInt(cpf[9])) return false;
  sum = 0;
  for (let i = 1; i <= 10; i++) sum += parseInt(cpf[i-1]) * (12 - i);
  rest = (sum * 10) % 11;
  if (rest === 10 || rest === 11) rest = 0;
  return rest === parseInt(cpf[10]);
}

export function formatCPF(cpf) {
  cpf = cpf.replace(/\D/g, '').slice(0,11);
  return cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
}

export function validateCNPJ(cnpj) {
  cnpj = cnpj.replace(/\D/g, '');
  if (cnpj.length !== 14) return false;
  // Validação básica: todos iguais
  if (/^(\d)\1+$/.test(cnpj)) return false;
  let size = cnpj.length - 2;
  let numbers = cnpj.substring(0, size);
  let digits = cnpj.substring(size);
  let sum = 0;
  let pos = size - 7;
  for (let i = size; i >= 1; i--) {
    sum += numbers[size - i] * pos--;
    if (pos < 2) pos = 9;
  }
  let result = sum % 11 < 2 ? 0 : 11 - sum % 11;
  if (result !== parseInt(digits[0])) return false;
  size++;
  numbers = cnpj.substring(0, size);
  sum = 0;
  pos = size - 7;
  for (let i = size; i >= 1; i--) {
    sum += numbers[size - i] * pos--;
    if (pos < 2) pos = 9;
  }
  result = sum % 11 < 2 ? 0 : 11 - sum % 11;
  return result === parseInt(digits[1]);
}

export function formatCNPJ(cnpj) {
  cnpj = cnpj.replace(/\D/g, '').slice(0,14);
  return cnpj.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5');
}

export function validateTelefone(tel) {
  tel = tel.replace(/\D/g, '');
  return tel.length === 11;
}

export function formatTelefone(tel) {
  tel = tel.replace(/\D/g, '').slice(0,11);
  return tel.replace(/(\d{2})(\d{5})(\d{4})/, '$1 $2-$3');
}

export function validateEmail(email) {
  if (!email || typeof email !== 'string') return false;
  if (email.length > 50) return false;
  return /^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(email);
}

export function formatEmail(email) {
  return email.slice(0,50);
}
