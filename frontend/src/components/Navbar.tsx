import { NavLink } from "react-router-dom";

export default function Navbar() {
  const linkBase = "text-sm font-medium hover:underline";
  const activeClass = "underline";

  return (
    <nav className="bg-white">
      <div className="flex items-center justify-between w-full max-w-[1130px] py-[22px] mx-auto">
        <NavLink to="/" className="inline-block">
          <img src="/assets/images/logos/logo.svg" alt="logo" />
        </NavLink>

        <ul className="flex items-center gap-[50px] w-fit">
          <li>
            <NavLink
              to="/browse"
              className={({ isActive }) =>
                `${linkBase} ${isActive ? activeClass : ""}`
              }
            >
              Browse
            </NavLink>
          </li>
          <li>
            <NavLink
              to="/popular"
              className={({ isActive }) =>
                `${linkBase} ${isActive ? activeClass : ""}`
              }
            >
              Popular
            </NavLink>
          </li>
          <li>
            <NavLink
              to="/categories"
              className={({ isActive }) =>
                `${linkBase} ${isActive ? activeClass : ""}`
              }
            >
              Categories
            </NavLink>
          </li>
          <li>
            <NavLink
              to="/events"
              className={({ isActive }) =>
                `${linkBase} ${isActive ? activeClass : ""}`
              }
            >
              Events
            </NavLink>
          </li>
          <li>
            <NavLink
              to="/check-booking"
              className={({ isActive }) =>
                `${linkBase} ${isActive ? activeClass : ""}`
              }
            >
              My Booking
            </NavLink>
          </li>
        </ul>

        <a
          href="#contact"
          className="flex items-center gap-[10px] rounded-full border border-[#000929] py-3 px-5"
          aria-label="Contact Us"
        >
          <img
            src="/assets/images/icons/call.svg"
            className="w-6 h-6"
            alt=""
            aria-hidden="true"
          />
          <span className="font-semibold">Contact Us</span>
        </a>
      </div>
    </nav>
  );
}
