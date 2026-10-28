export type AdminRole = "super_admin" | "admin" | "relecteur" | "editeur";

const rolePaths: Record<AdminRole, string> = {
  super_admin: "/admin/superadmin",
  admin: "/admin/admin",
  relecteur: "/admin/relecteur",
  editeur: "/admin/editeur",
};

const pathRoles: Record<string, AdminRole> = {
  superadmin: "super_admin",
  admin: "admin",
  relecteur: "relecteur",
  editeur: "editeur",
};

export function adminPathForRole(role: string): string {
  return rolePaths[role as AdminRole] ?? "/admin/connexion";
}

export function adminRoleForPath(path: string | undefined): AdminRole | undefined {
  return path ? pathRoles[path] : undefined;
}

export function adminRoleTitle(role: AdminRole, translate: (key: string) => string): string {
  const keys: Record<AdminRole, string> = {
    super_admin: "admin.roleSuper",
    admin: "admin.roleAdmin",
    relecteur: "admin.roleReviewer",
    editeur: "admin.roleEditor",
  };
  return translate(keys[role]);
}
